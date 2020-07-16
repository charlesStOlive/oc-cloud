<?php namespace Waka\Cloud\Classes;

//use Storage;
use ApplicationException;
use Config;
use October\Rain\Support\Collection;

/**
 * Description of Gd
 *
 * @author charles saint olive
 */
class FolderOrganisation
{
    private $configFolder;
    private $folderArray;

    public function __construct()
    {
        $this->configFolder = new Collection(Config::get('waka.crsm::cloud.folderModel'));
        if (!$this->configFolder) {
            throw new ApplicationException('La config folderModel est manquante');
        }
        $this->folderArray = [];
    }

    public function getFolder($model)
    {
        $this->folderArray = [];
        $this->recursiveSearch($model);
        return array_reverse($this->folderArray);
    }

    public function recursiveSearch($model)
    {
        $modelClass = get_class($model);
        //trace_log("modelClass " . $modelClass);
        $actualFolder = $this->configFolder->where('model', $modelClass)->first();
        //trace_log("actualFolder");
        //trace_log($actualFolder);
        if (!$actualFolder) {
            //trace_log('pas trouvé');
            return;
        }
        array_push($this->folderArray, $model[$actualFolder['name']]);
        array_push($this->folderArray, $actualFolder['folder']);
        if ($actualFolder['before'] ?? false) {
            $parentId = $model[$actualFolder['key']];
            $previousModel = new $actualFolder['before'];
            $previousModel = $previousModel::find($parentId);
            $this->recursiveSearch($previousModel);
        }
    }

}
