<?php

namespace App\Console\Commands\Elasticsearch;

use Illuminate\Console\Command;

class Migrate extends Command
{
    protected $es;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'es:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Elasticsearch Migrate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->es = app('es');

        $indices = [Indices\ProjectIndex::class];

        foreach ($indices as $indexClass) {
            $aliasName = $indexClass::getAliasName();
            $this->info('handle ' . $aliasName);

            if (!$this->es->indices()->exists(['index' => $aliasName])) {
                $this->info('index no exists, prepare to create');
                $this->createIndex($aliasName, $indexClass);
                $this->info('successfully created');
                $indexClass::rebuild($aliasName);
                $this->info('success');
                continue;
            }

            try {
                $this->info('index exists, prepare to update');
                $this->updateIndex($aliasName, $indexClass);
            } catch (\Exception $e) {
                $this->warn('update failed, prepare to rebuild');
                $this->reCreateIndex($aliasName, $indexClass);
            }

            $this->info('success created' . $aliasName);
        }
    }

    protected function createIndex($aliasName, $indexClass)
    {
        $this->es->indices()->create([
            'index' => $aliasName . '_0',
            'body' => [
                'settings' => $indexClass::getSettings(),
                'mappings' => [
                    'properties' => $indexClass::getProperties(),
                ],
                'aliases' => [
                    $aliasName => new \stdClass(),
                ],
            ],
        ]);
    }

    protected function updateIndex($aliasName, $indexClass)
    {
        $this->es->indices()->close([
            'index' => $aliasName,
        ]);

        $this->es->indices()->putSettings([
            'index' => $aliasName,
            'body' => $indexClass::getSettings(),
        ]);

        $this->es->indices()->putMapping([
            'index' => $aliasName,
            'body' => [
                'properties' => $indexClass::getProperties(),
            ],
        ]);

        $this->es->indices()->open([
            'index' => $aliasName,
        ]);
    }

    protected function reCreateIndex($aliasName, $indexClass)
    {
        $indexInfo = $this->es->indices()->getAliases([
            'index' => $aliasName,
        ]);

        $indexName = array_keys($indexInfo)[0];

        if (!preg_match('~_(\d+)$~', $indexName, $m)) {
            $msg = 'index is not correct :' . $indexName;
            $this->error($msg);
            throw new \Exception($msg);
        }

        $newIndexName = $aliasName . '_' . ($m[1] + 1);
        $this->info('creating index:' . $newIndexName);
        $this->es->indices()->create([
            'index' => $newIndexName,
            'body' => [
                'settings' => $indexClass::getSettings(),
                'mappings' => [
                    'properties' => $indexClass::getProperties(),
                ],
            ],
        ]);

        $this->info('created success');
        $indexClass::rebuild($newIndexName);
        $this->info('rebuild success');
        $this->es->indices()->putAlias([
            'index' => $newIndexName,
            'name' => $aliasName,
        ]);
        $this->info('updated success, prepare to delete old index');
        $this->es->indices()->delete([
            'index' => $indexName,
        ]);

        $this-> info('success');
    }
}
