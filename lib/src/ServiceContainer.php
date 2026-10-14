<?php

declare(strict_types=1);

namespace Omi;

use Omi\Api\Action\CollectionAction;
use Omi\Api\Action\InfoAction;
use Omi\Api\Action\PppoeCredentialsAction;
use Omi\Api\Router;
use Omi\Collection\CollectionProvider;
use Omi\Collection\CustomerCollection;
use Omi\Collection\GponZteOnuCollection;
use Omi\Collection\NetDevCollection;
use Omi\Collection\NetNodeCollection;
use Omi\Collection\NodeAccessConfigurationCollection;
use Omi\Collection\NodeCollection;
use Omi\Collection\NodeGroupCollection;
use Omi\Collection\TariffCollection;
use Omi\Config\ConfigSource;
use Omi\Config\OmiConfig;
use Omi\Device\DescriptionToAddressConverter;
use Omi\Device\ErrorDeviceList;
use Omi\Http\CurlTransport;
use Omi\Http\HttpTransport;
use Omi\Lms\Database;
use Omi\Lms\LmsApi;
use Omi\OltManager\OltManagerClient;
use Omi\Pppoe\PppoeCredentialsFinder;
use Omi\Proxy\OltManagerProxy;
use Omi\Repository\CustomerAddressBook;
use Omi\Repository\CustomerEmails;
use Omi\Repository\GponZteRepository;
use Omi\Repository\NetDevLinkedOwners;
use Omi\Repository\NetDevLocationResolver;
use Omi\Repository\NetDevRepository;
use Omi\Repository\NodeAccessConfigurationRepository;
use Omi\Repository\NodeGroupRepository;
use Omi\Repository\UserRepository;
use Omi\Security\CsrfTokenManager;
use Omi\Security\SessionStore;
use Omi\Sso\CurrentUser;
use Omi\Sso\SsoRedirector;
use Omi\Support\Logger;

/**
 * Mały kontener usług pluginu: tworzy obiekty leniwie i współdzieli je w obrębie żądania.
 * Zależności LMS (baza, obiekt LMS, sesja, bieżący użytkownik) są wstrzykiwane z zewnątrz.
 */
final class ServiceContainer
{
    private string $pluginVersion;
    private ConfigSource $configSource;
    private Database $database;
    private LmsApi $lms;
    private SessionStore $session;
    /** @var callable(): ?int */
    private $currentUserIdResolver;
    private ?HttpTransport $transport;

    /** @var array<string, object> */
    private array $services = [];

    public function __construct(
        string $pluginVersion,
        ConfigSource $configSource,
        Database $database,
        LmsApi $lms,
        SessionStore $session,
        callable $currentUserIdResolver,
        ?HttpTransport $transport = null
    ) {
        $this->pluginVersion = $pluginVersion;
        $this->configSource = $configSource;
        $this->database = $database;
        $this->lms = $lms;
        $this->session = $session;
        $this->currentUserIdResolver = $currentUserIdResolver;
        $this->transport = $transport;
    }

    public function config(): OmiConfig
    {
        return $this->shared(OmiConfig::class, function (): OmiConfig {
            return new OmiConfig($this->configSource);
        });
    }

    public function logger(): Logger
    {
        return $this->shared(Logger::class, static function (): Logger {
            return new Logger();
        });
    }

    public function oltManagerClient(): OltManagerClient
    {
        return $this->shared(OltManagerClient::class, function (): OltManagerClient {
            $config = $this->config();
            $transport = $this->transport ?? new CurlTransport(
                $config->sslVerify(),
                $config->oltManagerDirectIp(),
                $this->logger(),
                'LMSOmiPlugin/' . $this->pluginVersion
            );

            return new OltManagerClient($config->oltManagerUrl(), $config->oltManagerToken(), $transport);
        });
    }

    public function users(): UserRepository
    {
        return $this->shared(UserRepository::class, function (): UserRepository {
            return new UserRepository($this->database);
        });
    }

    public function currentUserId(): ?int
    {
        $id = ($this->currentUserIdResolver)();

        return $id !== null && $id > 0 ? $id : null;
    }

    public function currentUser(): ?CurrentUser
    {
        $id = $this->currentUserId();
        if ($id === null) {
            return null;
        }
        $login = $this->users()->loginById($id);

        return $login === null ? null : new CurrentUser($id, $login);
    }

    public function ssoRedirector(): SsoRedirector
    {
        return $this->shared(SsoRedirector::class, function (): SsoRedirector {
            return new SsoRedirector($this->config(), $this->oltManagerClient(), $this->logger());
        });
    }

    public function proxy(): OltManagerProxy
    {
        return $this->shared(OltManagerProxy::class, function (): OltManagerProxy {
            return new OltManagerProxy($this->config(), $this->oltManagerClient(), $this->ssoRedirector());
        });
    }

    public function csrf(): CsrfTokenManager
    {
        return $this->shared(CsrfTokenManager::class, function (): CsrfTokenManager {
            return new CsrfTokenManager($this->session);
        });
    }

    public function apiRouter(): Router
    {
        return $this->shared(Router::class, function (): Router {
            $actions = [];
            foreach ($this->collections() as $collection) {
                $actions[] = new CollectionAction($collection);
            }
            $actions[] = new PppoeCredentialsAction($this->pppoe());

            $names = [];
            foreach ($actions as $action) {
                $names[] = $action->name();
            }
            array_unshift($actions, new InfoAction($this->pluginVersion, $names, $this->config()));

            return new Router($actions, $this->logger());
        });
    }

    public function errorDeviceList(): ErrorDeviceList
    {
        return new ErrorDeviceList($this->lms, $this->netDevRepository(), new DescriptionToAddressConverter());
    }

    /**
     * @return list<CollectionProvider>
     */
    public function collections(): array
    {
        return [
            new CustomerCollection($this->lms, $this->customerAddressBook(), new CustomerEmails($this->database), $this->config()),
            new NodeCollection($this->lms),
            new NetDevCollection($this->netDevRepository(), $this->netDevLocationResolver()),
            new NetNodeCollection($this->lms),
            new TariffCollection($this->lms),
            new NodeAccessConfigurationCollection(new NodeAccessConfigurationRepository($this->database), $this->config()),
            new NodeGroupCollection(new NodeGroupRepository($this->database)),
            new GponZteOnuCollection(new GponZteRepository($this->database)),
        ];
    }

    private function pppoe(): PppoeCredentialsFinder
    {
        return new PppoeCredentialsFinder($this->lms);
    }

    private function customerAddressBook(): CustomerAddressBook
    {
        return $this->shared(CustomerAddressBook::class, function (): CustomerAddressBook {
            return new CustomerAddressBook($this->database);
        });
    }

    private function netDevRepository(): NetDevRepository
    {
        return $this->shared(NetDevRepository::class, function (): NetDevRepository {
            return new NetDevRepository($this->database, $this->customerAddressBook());
        });
    }

    private function netDevLocationResolver(): NetDevLocationResolver
    {
        return $this->shared(NetDevLocationResolver::class, function (): NetDevLocationResolver {
            return new NetDevLocationResolver($this->customerAddressBook(), new NetDevLinkedOwners($this->database));
        });
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @param callable(): T $factory
     * @return T
     */
    private function shared(string $id, callable $factory)
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = $factory();
        }

        /** @var T */
        return $this->services[$id];
    }
}
