<?php namespace Waka\Cloud\Classes\Cloud;

use File;
use Storage;

/**
 * Description of Gd
 *
 * @author charles saint olive
 */
class Gd
{
    
    /**
     * Absence d'interface pour le moement les fonctionq communes aux autres Classes de Cloud sont dans cette première partie.
     */
    public function put($pathAndFileName, $content) {
        $pathObject = $this->convertPathToObject($pathAndFileName);
        $folderpath_array = $pathObject['path_array'] ?? '/';
        $lastFolderObj = $this->createFolderFromArray($folderpath_array);
        $lastFolderId = $lastFolderObj['path'] ?? '/';
        $fileName = $pathObject['basename'];
        \Storage::cloud()->put($lastFolderId.'/'.$fileName, $content);
    }
    public function listFolderItems($folderPath)
    {
        // $folder For simplicity, this folder is assumed to exist in the root directory.
        $pathObject = $this->convertPathToObject($folderPath);
        $folderpath_array = $pathObject['path_array'] ?? '/';
        $lastFolderObj = $this->createFolderFromArray($folderpath_array);
        $lastFolderId = $lastFolderObj['path'] ?? '/';

        $files = collect(Storage::cloud()->listContents($lastFolderId, false))
            ->where('type', '=', 'file');
        $files = $files->mapWithKeys(function ($file) {
            $filename = $file['filename'] . '.' . $file['extension'];
            $path = $file['path'];
            return [$path => $filename];
        });
        return $files;
    }
    public function getRawFile($pathAndFIleName)
    {
        $pathFileObject = $this->convertPathToObject($pathAndFIleName);
        //trace_log($pathFileObject);
        $folderpath_array = $pathFileObject['path_array'] ?? '/';
        $lastFolderObj = $this->createFolderFromArray($folderpath_array);
        $lastFolderId = $lastFolderObj['path'] ?? '/';
        //trace_log($lastFolderId);
       


        $contents = collect(Storage::cloud()->listContents($lastFolderId, $recursive = true));
        //trace_log($contents);

        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', $pathFileObject['filename'])
            ->where('extension', '=', $pathFileObject['extension'])
            ->first(); // there can be duplicate file names!

        //return $file; // array with file info
        //trace_log($file['path']);

        return Storage::cloud()->get($file['path']);
    }
    //
    


    /**
     * Interne *****************************************************************
     */
    private function findOrCreateDir($folderPath)
    {
        $pathObject = $this->convertPathToObject($folderPath);
        $folderpath_array = $pathObject['path_array'] ?? '/';
        $lastFolderObject = $this->createFolderFromArray($folderpath_array);
        return $lastFolderObject['path'] ?? null;
    }
    
    //
    
    //

    private function createFolderFromArray($folderArray)
    {
        if($folderArray == '/') {
            return '/';
        }
        $newDir = false;
        foreach ($folderArray as $folder) {
            if (!$newDir) {
                $newDir = $this->findOrCreateParentDir($folder);
            } else {
                $newDir = $this->findOrCreateParentDir($folder, $newDir);
            }
        }
        // retourne le dernier dossier crée.
        return $newDir;
    }
    private function findOrCreateParentDir($folderName, $parentDir = false)
    {
        $dir = $this->getDirId($folderName, $parentDir);
        if (!$dir) {
            $dir = Storage::cloud()->makeDirectory($parentDir['path'] . '/' . $folderName);
            $dir = $this->getDirId($folderName, $parentDir);
        }
        return $dir;
    }
    

    private function convertPathToObject($pathAndFIleName) {
        $pathAndFIleName = str_replace("\\", "/", $pathAndFIleName);
        $pathAndFIle = (bool)strpos($pathAndFIleName, '.');
        if($pathAndFIle) {
            $path_parts = pathinfo($pathAndFIleName);

            return [
                'basename' => $path_parts['basename'] ?? null,
                'filename' => $path_parts['filename'] ?? null,
                'extension' => $path_parts['extension'] ?? null,
                'path' => $path_parts['dirname'] ?? null,
                'path_array' => array_filter(explode('/', $path_parts['dirname']),'strlen'),

            ];
        } else {
            return [
                'basename' =>  null,
                'filename' => null,
                'extension' => null,
                'path' => $pathAndFIleName,
                'path_array' => array_filter(explode('/', $pathAndFIleName),'strlen'),
            ];
        }
        
    }

    private function convertArrayPathToId($folderCollection) {
        $newDirId = false;
        foreach ($folderCollection as $folder) {
            if (!$newDirId) {
                $newDirId = $this->getDirId($folder);
            } else {
                $newDirId = $this->getDirId($folder, $newDirId);
            }
        }
        return $newDirId;
    }

    private function getDirId($folderName, $_parentDir = false)
    {
        $parentDir = [];
        if (!$_parentDir) {
            $parentDir['path'] = '/';
        } else {
            $parentDir = $_parentDir;
        }

        $contents = collect(Storage::cloud()->listContents($parentDir['path'], false));
        $dir = $contents->where('type', '=', 'dir')
            ->where('filename', '=', $folderName)
            ->first(); // There could be duplicate directory names!

        return $dir;
    }
}
