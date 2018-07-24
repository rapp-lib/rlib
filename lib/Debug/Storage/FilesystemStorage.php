<?php
namespace R\Lib\Debug\Storage;

use Symfony\Component\Finder\Finder;
use Barryvdh\Debugbar\Storage\FilesystemStorage as LaravelFilesystemStorage;

class FilesystemStorage extends LaravelFilesystemStorage
{
    /**
     * {@inheritDoc}
     */
    public function find(array $filters = array(), $max = 20, $offset = 0)
    {
        // Sort by modified time, newest first
        $sort = function (\SplFileInfo $a, \SplFileInfo $b) {
            return strcmp($b->getMTime(), $a->getMTime());
        };

        // Loop through .json files, filter the metadata and stop when max is found.
        $i = 0;
        $results = array();
        foreach (Finder::create()->files()->name('*.json')->in($this->dirname)->sort($sort) as $file) {
            if ($i++ < $offset && empty($filters)) {
                $results[] = null;
                continue;
            }
            $data = json_decode($this->files->get($file->getRealPath()), true);
            $meta = $data['__meta'];
            unset($data);
            if ($this->filter($meta, $filters)) {
                $results[] = $meta;
            }
            if (count($results) >= ($max + $offset)) {
                break;
            }
        }
        return array_slice($results, $offset, $max);
    }
}