<?php


namespace Tartan\Larapay\Console;

use Illuminate\Console\Command;
use Illuminate\Console\DetectsApplicationNamespace;

class InstallCommand extends Command
{
    use DetectsApplicationNamespace;

    protected $signature = 'larapay:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'install larapay views and routes';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();

        //$this->exportViews();


        file_put_contents(app_path('Http/Controllers/LarapayController.php'), $this->compileControllerStub());

        file_put_contents(base_path('routes/web.php'), file_get_contents(__DIR__ . '/stubs/routes.stub'),
            FILE_APPEND);


        $this->info('Larapay scaffolding generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = resource_path('views/layouts'))) {
            mkdir($directory, 0755, true);
        }

        if (!is_dir($directory = resource_path('views/auth/passwords'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            if (file_exists($view = resource_path('views/' . $value)) && !$this->option('force')) {
                if (!$this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(__DIR__ . '/stubs/make/views/' . $key, $view);
        }
    }

    /**
     * Compiles the HomeController stub.
     *
     * @return string
     */
    protected function compileControllerStub()
    {
        return str_replace('{{namespace}}', $this->getAppNamespace(),
            file_get_contents(__DIR__ . '/stubs/controllers/LarapayController.stub'));
    }


}