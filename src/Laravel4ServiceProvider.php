<?php namespace BackupManager\Laravel;

use BackupManager\Databases;
use BackupManager\Filesystems;
use BackupManager\Compressors;
use Symfony\Component\Process\Process;
use Illuminate\Support\ServiceProvider;
use BackupManager\Config\Config;
use BackupManager\ShellProcessing\ShellProcessor;

/**
 * Class BackupManagerServiceProvider
 * @package BackupManager\Laravel
 */
class Laravel4ServiceProvider extends ServiceProvider {
    use GetDatabaseConfig;

    /** @var bool */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot() {
        $this->package('backup-manager/laravel', 'backup-manager', realpath(app_path("config")));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register() {
        $this->registerFilesystemProvider();
        $this->registerDatabaseProvider();
        $this->registerCompressorProvider();
        $this->registerShellProcessor();
        $this->registerArtisanCommands();
    }

    /**
     * Register the filesystem provider.
     *
     * @return void
     */
    private function registerFilesystemProvider() {
        $this->app->bind(\BackupManager\Filesystems\FilesystemProvider::class, function ($app) {
            $provider = new Filesystems\FilesystemProvider(new Config($app['config']['backup-manager']));
            $provider->add(new Filesystems\Awss3Filesystem);
            $provider->add(new Filesystems\GcsFilesystem);
            $provider->add(new Filesystems\DropboxFilesystem);
            $provider->add(new Filesystems\FtpFilesystem);
            $provider->add(new Filesystems\LocalFilesystem);
            $provider->add(new Filesystems\RackspaceFilesystem);
            $provider->add(new Filesystems\SftpFilesystem);
            return $provider;
        });
    }

    /**
     * Register the database provider.
     *
     * @return void
     */
    private function registerDatabaseProvider() {
        $this->app->bind(\BackupManager\Databases\DatabaseProvider::class, function ($app) {
            $provider = new Databases\DatabaseProvider($this->getDatabaseConfig($app['config']['database.connections']));
            $provider->add(new Databases\MysqlDatabase);
            $provider->add(new Databases\PostgresqlDatabase);
            return $provider;
        });
    }

    /**
     * Register the compressor provider.
     *
     * @return void
     */
    private function registerCompressorProvider() {
        $this->app->bind(\BackupManager\Compressors\CompressorProvider::class, function () {
            $provider = new Compressors\CompressorProvider;
            $provider->add(new Compressors\GzipCompressor);
            $provider->add(new Compressors\NullCompressor);
            return $provider;
        });
    }

    /**
     * Register the filesystem provider.
     *
     * @return void
     */
    private function registerShellProcessor() {
        $this->app->bind(\BackupManager\ShellProcessing\ShellProcessor::class, function () {
            return new ShellProcessor(new Process('', null, null, null, null));
        });
    }

    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerArtisanCommands() {
        $this->commands([
            \BackupManager\Laravel\Laravel4DbBackupCommand::class,
            \BackupManager\Laravel\Laravel4DbRestoreCommand::class,
            \BackupManager\Laravel\Laravel4DbListCommand::class
        ]);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides() {
        return [
            \BackupManager\Filesystems\FilesystemProvider::class,
            \BackupManager\Databases\DatabaseProvider::class,
            \BackupManager\ShellProcessing\ShellProcessor::class,
        ];
    }
}
