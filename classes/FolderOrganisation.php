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
        //On retrouve la classe du modele et on cherche dans la config cloud de crsm
        $modelClass = get_class($model);
        $actualFolder = $this->configFolder->where('model', $modelClass)->first();
        $actualFolderKey = $this->configFolder->where('model', $modelClass)->keys()->first();

        if (!$actualFolder) {
            //trace_log('pas trouvé');
            throw new ApplicationException('Impossible de trouver une config pour ce modèle');
        }
        //On rentre les infos de ce dossier.

        //recherche du champs qui donnera le nom du dossier par default slug
        $folderName = $actualFolder['column_for_name'] ?? 'slug';
        array_push($this->folderArray, $model[$folderName]);

        //si il existe un champ folder on crée un dossier
        if ($actualFolder['folder'] ?? false) {
            array_push($this->folderArray, $actualFolder['folder']);
        }

        //On va chercher des informations dans les dossier parents.
        if ($actualFolder['before'] ?? false) {

            //On determine la clé de liaison du model parent
            $parentKey = $actualFolder['key'] ?? $actualFolderKey . '_id';
            $parentId = $model[$parentKey];

            $previousModel = new $actualFolder['before'];
            $previousModel = $previousModel::find($parentId);
            $this->recursiveSearch($previousModel);
        }
    }

}
