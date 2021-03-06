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

    public function listFolderItems($folderName, $previousFolder = "/", $onlyfileName = true)
    {
        // $folder For simplicity, this folder is assumed to exist in the root directory.

        // Get root directory contents...
        $contents = collect(Storage::cloud()->listContents($previousFolder, false));

        // Find the folder you are looking for...
        $dir = $contents->where('type', '=', 'dir')
            ->where('filename', '=', $folderName)
            ->first(); // There could be duplicate directory names!

        if (!$dir) {
            //trace_log("Le repertoire " . $folderName . " n'existe pas");
            return false;
        }

        // Get the files inside the folder...
        $files = collect(Storage::cloud()->listContents($dir['path'], false))
            ->where('type', '=', 'file');

        //trace_log($files);

        $files = $files->mapWithKeys(function ($file) use ($onlyfileName) {
            $filename = $file['filename'] . '.' . $file['extension'];
            $path = $file['path'];

            // Use the path to download each file via a generated link..
            // Storage::cloud()->get($file['path']);

            $arrayFile = [$path => $filename];
            if ($onlyfileName) {
                $arrayFile = [$filename => $filename];
            }

            return $arrayFile;
        });
        return $files;
    }

    public function findOrCreateDir($folderName, $parentDir = false)
    {
        //trace_log("recherche du dossier ||");
        //trace_log($folderName);
        //trace_log($parentDir);

        $dir = $this->getDir($folderName, $parentDir);
        //trace_log("si dossier existant");
        //trace_log($dir);

        if (!$dir) {
            //trace_log("si dossier pas existant");
            $dir = Storage::cloud()->makeDirectory($parentDir['path'] . '/' . $folderName);
            //trace_log($dir);
            $dir = $this->getDir($folderName, $parentDir);
            //trace_log($dir);
        }

        return $dir;
    }

    public function getDir($folderName, $_parentDir = false)
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

    public function getFileInDir($pathAndFIleName, $is_array = false)
    {
        $pathAndFIleName = str_replace("\\", "/", $pathAndFIleName);
        $splitPath = explode('/', $pathAndFIleName);
        $dirFileCollection = $splitPath;
        $fileName = array_pop($dirFileCollection);

        $newDir = false;
        foreach ($dirFileCollection as $folder) {
            if (!$newDir) {
                $newDir = $this->getDir($folder);
            } else {
                $newDir = $this->getDir($folder, $newDir);
            }
        }
        $contents = collect(Storage::cloud()->listContents($newDir, false));
        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', pathinfo($fileName, PATHINFO_FILENAME))
            ->where('extension', '=', pathinfo($fileName, PATHINFO_EXTENSION))
            ->first(); // there can be duplicate file names!

        $rawData = Storage::cloud()->get($file['path']);

        return response($rawData, 200)
            ->header('ContentType', $file['mimetype'])
            ->header('Content-Disposition', "attachment; filename='$filename'");
    }

    public function putFileInDir($pathAndFIleName, $fileToPutPath)
    {
        $pathAndFIleName = str_replace("\\", "/", $pathAndFIleName);
        $splitPath = explode('/', $pathAndFIleName);
        $dirFileCollection = $splitPath;
        $fileName = array_pop($dirFileCollection);

        $newDir = false;
        foreach ($dirFileCollection as $folder) {
            if (!$newDir) {
                $newDir = $this->findOrCreateDir($folder);
            } else {
                $newDir = $this->findOrCreateDir($folder, $newDir);
            }
        }
        $fileData = File::get($fileToPutPath);

        Storage::cloud()->put($newDir['path'] . '/' . $fileName, $fileData);
    }

    public function createDirFromArray($folderArray)
    {
        //trace_log($folderArray);
        $dirFileCollection = $folderArray;
        $newDir = false;
        foreach ($dirFileCollection as $folder) {
            if (!$newDir) {
                //trace_log("pas de newDir");
                $newDir = $this->findOrCreateDir($folder);
            } else {
                $newDir = $this->findOrCreateDir($folder, $newDir);
            }
        }
        // retourne le dernier dossier crée.
        //trace_log("New Dir recréee ||");
        //trace_log($newDir);
        return $newDir;
    }

    public function getFile($fileName, $folderPath = "/")
    {
        $dir = $folderPath;
        $recursive = false; // Get subdirectories also?
        $contents = collect(Storage::cloud()->listContents($dir, $recursive));

        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', pathinfo($fileName, PATHINFO_FILENAME))
            ->where('extension', '=', pathinfo($fileName, PATHINFO_EXTENSION))
            ->first(); // there can be duplicate file names!

        //return $file; // array with file info

        $rawData = Storage::cloud()->get($file['path']);

        return response($rawData, 200)
            ->header('ContentType', $file['mimetype'])
            ->header('Content-Disposition', "attachment; filename='$filename'");
    }

    public function getRawFile($fileName, $folderPath = "/")
    {
        $dir = $folderPath;
        $recursive = false; // Get subdirectories also?
        $contents = collect(Storage::cloud()->listContents($dir, $recursive));

        $file = $contents
            ->where('type', '=', 'file')
            ->where('filename', '=', pathinfo($fileName, PATHINFO_FILENAME))
            ->where('extension', '=', pathinfo($fileName, PATHINFO_EXTENSION))
            ->first(); // there can be duplicate file names!

        //return $file; // array with file info

        return Storage::cloud()->get($file['path']);
    }
}
