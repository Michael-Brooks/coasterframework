<?php

namespace CoasterCms\Console\Commands;

use CoasterCms\Console\Assets as Assets;
use Illuminate\Console\Command;

class UpdateAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coaster:update-assets 
        {assets?* : $assets} 
        {--force : Update assets file regardless of stored version}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads/Updates public asset files required by the admin interface & captcha.';

    /**
     * @var array
     */
    protected $_folders;

    /**
     * @var array
     */
    protected $_assetNames;

    /**
     * @var array
     */
    protected $_assetInstalledVersions;

    /**
     * @var string
     */
    protected $_assetInstallFile;

    /**
     * @var array
     */
    protected $_assets = [
        Assets\App::class,
        Assets\Bootstrap::class,
        Assets\AceEditor::class,
        Assets\FileManager::class,
        Assets\JQuery::class
    ];

    /**
     * UpdateAssets constructor.
     */
    public function __construct()
    {
        foreach ($this->_assets as $asset) {
            $this->_assetNames[$asset::$name] = $asset;
        }
        $this->signature = str_replace('$assets', implode(' ', $this->_assetNames), $this->signature);
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->_initVersions();

        $this->info('Running public asset file update:');
        if ($assets = $this->input->getArgument('assets')) {
            $updateAssets = array_intersect_key($this->_assetNames, array_fill_keys($assets, null));
        } else {
            $updateAssets = $this->_assetNames;
        }
        $bar = $this->output->createProgressBar(count($updateAssets));
        $bar->setFormatDefinition('custom', ' %current%/%max% [%bar%] - %message%');
        $bar->setFormat('custom');
        $bar->setMessage('Initializing ...');
        $bar->display();

        $errors = [];
        foreach ($updateAssets as $assetName => $assetClass) {
            $bar->setMessage('Updating: ' . ($assetClass::$description ?: $assetName) . ' (' . $assetClass::$version . ')');
            $bar->display();
            /** @var Assets\AbstractAsset $asset */
            $asset = new $assetClass($this->_folders['public'], $this->_assetInstalledVersions[$assetName], $this->input->getOption('force'), $bar);
            try {
                $this->_setVersion($assetName, $asset->execute());
            } catch (\Exception $e) {
                $errors[$assetName] = $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']';
            }
            $bar->advance();
        }

        $bar->setMessage('Finished' . ($errors ? ' with errors' : ''));
        $bar->finish();
        $this->info("\n");
        if ($errors) {
            foreach ($errors as $assetName => $error) {
                $this->error($assetName . ': ' . $error);
            }
        }
    }

    /**
     *
     */
    protected function _initVersions()
    {
        $this->_folders['public'] = public_path(trim(config('coaster::admin.public'), '/')) . '/';
        $this->_folders['storage'] = storage_path(trim(config('coaster::site.storage_path'), '/')) . '/';
        foreach ($this->_folders as $folder) {
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
        }
        $this->_assetInstallFile = $this->_folders['storage'] . '/assets.json';
        if (file_exists($this->_assetInstallFile)) {
            $this->_assetInstalledVersions = json_decode(file_get_contents($this->_assetInstallFile), true);
        } else {
            $this->_assetInstalledVersions = [];
        }
        foreach ($this->_assetNames as $asset) {
            $this->_assetInstalledVersions[$asset::$name] = array_key_exists($asset::$name, $this->_assetInstalledVersions) ? $this->_assetInstalledVersions[$asset::$name] : '';
        }
    }

    /**
     * @param string $assetName
     * @param string $newVersion
     */
    protected function _setVersion($assetName, $newVersion)
    {
        if ($this->_assetInstalledVersions[$assetName] != $newVersion) {
            $this->_assetInstalledVersions[$assetName] = $newVersion;
            file_put_contents($this->_assetInstallFile, json_encode($this->_assetInstalledVersions));
        }
    }

}
