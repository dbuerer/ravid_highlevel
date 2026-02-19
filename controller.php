<?php
namespace Concrete\Package\RavidHighlevel;
use Concrete\Core\Package\Package;
use Log;
use BlockType;
use BlockTypeSet;
use Loader;
use SinglePage;
use PageTheme;
use Concrete\Core\Entity\Attribute\Key\PageKey;
use Concrete\Core\Entity\Attribute\Key\UserKey;
use Concrete\Core\Entity\Attribute\Key\EventKey;
use RavidTools\CommonFunctions;
use Concrete\Core\Support\Facade\DatabaseORM;


defined('C5_EXECUTE') or die(_("Access Denied."));

class Controller extends Package
{
	
	protected $pkgHandle = 'ravid_highlevel';
	protected $appVersionRequired = '9.0.0';
	protected $pkgVersion = '0.0.1';
	protected $pkgAllowsFullContentSwap = false;
	protected $pkgAutoloaderRegistries = array(		
		'src'=>'\RavidHighLevel'		
	);	
	protected $packageDependencies = [
		'ravid_tools'=>'1.0.0'
	];	
	
	public function gitHubUser(){return 'dbuerer';} //used by ravid updater
	public function gitHubRepository(){return 'c5-ravid_highlevel';} //used by ravid updater

		public function getPackageDescription(){
	    return t('Tools used to work in concert with a High Level compatible CRM:
                    <UL>
                    	<LI>Block to receive attributes from a trigger link and display them on the page
                    </UL>');
	}
	
	public function getPackageName(){	    
	    return t('RAVID Enterprises High Level CRM Support Tools');	    
	}
    

	public function on_start(){
		require $this->getPackagePath() . '/vendor/autoload.php';
		
	}
		
	public function install(){
	    \Log::addDebug("Installing $this->pkgHandle");
		$this->setupAutoloader();
	    $pkg=parent::install();	    
	    
	    $this->installAndUpgrade($pkg);

	    return $pkg;
	}
	
	public function upgrade(){	    
	    \Log::addDebug("Updating $this->pkgHandle");
		$this->setupAutoloader();
	    parent::upgrade();	    
	    	    
		$pkgService = $this->app->make(\Concrete\Core\Package\PackageService::class);
		$pkg = $pkgService->getByHandle($this->pkgHandle);
	    $this->installAndUpgrade($pkg);
	    return $pkg;
	}
	
	public function installAndUpgrade($pkg)
	{   
	    $db=\Loader::db();

		//do xml install .
		$this->installContentFile('install.xml');
		
		//install attributes		
    	$this->installAttributeTypes($pkg);
    			
		//install blocks    	
        $this->installBlocks($pkg);       
	       
        //create orm entities
        if(is_dir($_SERVER['DOCUMENT_ROOT']. $pkg->getRelativePath() . '/src/Entity')){
            \Log::addDebug("Updating Entities");
            $em = DatabaseORM::entityManager();
            $manager = new \Concrete\Core\Database\DatabaseStructureManager($em);
            $manager->refreshEntities();
            \Log::addDebug("Refresh Entities Complete");
        }
        

		//update configuration
		$cfg=$pkg->getConfig();
    	if(!$cfg->has('ravid.gitHubPersonalToken')) $cfg->save('ravid.gitHubPersonalToken','undefined');		

		//install single pages
		/*
		$page=SinglePage::add('/dashboard/system/ravid_enterprises/update',$pkg);
        $page->update(array(
            'cName' => 'Update RAVID Packages from GitHub',
        ));

		$page=SinglePage::add('/dashboard/system/ravid_enterprises/favicongenerator',$pkg);
        $page->update(array(
            'cName' => 'Favicon Generator',
        ));
		*/
	}
       
		
	
	//** COMMON FUNCTIONS SHARED BETWEEN RAVID PROJECTS */
	
	private function installPermissionKey($permissionCategoryHandle,$permissionHandle,$title,$description,$triggerWorkflow,$customClass,$package){
	    $key=\Concrete\Core\Permission\Key\Key::getByHandle($permissionHandle);
	    if(!is_object($key)){
	        return \Concrete\Core\Permission\Key\Key::add($permissionCategoryHandle,$permissionHandle,$title,$description,$triggerWorkflow,$customClass,$package);
	    }
	}
	
	private function installBlocks($pkg){    
	    \Log::addDebug("installing blocks for " . $pkg->getPackageName());
	    //install bocks out of blocks directory
	    $blockDir=$_SERVER['DOCUMENT_ROOT'].$pkg->getRelativePath() . '/blocks';
	    
	    if(is_dir($blockDir)){
	        $d=opendir($blockDir);
	        while(false!==($f=readdir($d))){	            
	            if($f!=='.' AND $f!=='..' AND is_dir("$blockDir/$f")){
	                $controllerFname="$blockDir/$f/controller.php";
	                if(!file_exists($controllerFname)){
	                    \Log::addDebug("No Controller for block $f ($controllerFname)");
	                }else{
	                    \Log::addDebug("Installing block $f");
	                    $controllerFile=file_get_contents($controllerFname);
	                    $blockSetHandle=CommonFunctions::getVariableValueFromFile('btDefaultSet',$controllerFile);
	                    $blockSetDesc=CommonFunctions::getVariableValueFromFile('blockSetDesc',$controllerFile);
	                    if(strlen($blockSetDesc)==0) $blockSetDesc=ucwords(str_replace('_',' ',$blockSetHandle));
	                    if (!is_object(BlockTypeSet::getByHandle($blockSetHandle))) {
	                            BlockTypeSet::add($blockSetHandle, $blockSetDesc, $pkg);
	                            \Log::addDebug("Added block set handle $blockSetHandle with description $blockSetDesc");
                        }	                    
	                    
	                    //install block
                        if(!is_object(BlockType::getByHandle($f))) {
                            //deprecated - BlockType::installBlockTypeFromPackage($f,$pkg);
                            BlockType::installBlockType($f,$pkg);
                        }
	                    
	                }
	            }else{
	                if($f!=='.' AND $f!=='..') \Log::addDebug("$blockDir/$f is not a directory");    
	            }
	        }
	    }else{
	        \Log::addDebug("no blocks to install ($blockDir)");
	    }
	}
	
	private function installAttributeTypes($pkg){
	    \Log::addDebug("installing Attrbiutes for " . $pkg->getPackageName());
	    //install attributes out of blocks directory
	    $attrDir=$_SERVER['DOCUMENT_ROOT'].$pkg->getRelativePath() . '/attributes';
	    
	    if(is_dir($attrDir)){
	        $d=opendir($attrDir);
	        while(false!==($f=readdir($d))){
	            if($f!=='.' AND $f!=='..' AND is_dir("$attrDir/$f")){
	                $controllerFname="$attrDir/$f/controller.php";
	                if(!file_exists($controllerFname)){
	                    \Log::addDebug("No Controller for attribute $f ($controllerFname)");
	                }else{
	                    \Log::addDebug("Installing attributes $f");	        
	                    
	                    $controllerFile=file_get_contents($controllerFname);
	                    $attrHandle=CommonFunctions::getVariableValueFromFile('attrHandle',$controllerFile);
	                    $attrName=CommonFunctions::getVariableValueFromFile('attrName',$controllerFile);
	                    $attrType=CommonFunctions::getVariableValueFromFile('attrType',$controllerFile);
	                    $attrSearchable=CommonFunctions::getVariableValueFromFile('searchable',$controllerFile);
	                    $attrSettings=CommonFunctions::getVariableValueFromFile('settings',$controllerFile);
	                    $attrSetHandle=CommonFunctions::getVariableValueFromFile('attrSetHandle',$controllerFile);
	                    $attrSetName=CommonFunctions::getVariableValueFromFile('attrSetName',$controllerFile);
	                    $attrCategoryServiceHandle=explode(',',CommonFunctions::getVariableValueFromFile('attrCategoryServiceHandle',$controllerFile));	                    
	                    
						//intall attribute
	                    $this->installAttribute($pkg,$attrHandle,$attrName,$attrType,$attrSettings,$attrSearchable,$attrSetHandle,$attrSetName);
	                   
						//assign to approrpaite attribute category
						$factory = $this->app->make('Concrete\Core\Attribute\TypeFactory');
    					$type = $factory->getByHandle($attrHandle);
						$service = $this->app->make('Concrete\Core\Attribute\Category\CategoryService');
						foreach($attrCategoryServiceHandle AS $thisServiceHandle){
							$category = $service->getByHandle($thisServiceHandle)->getController();
							$category->associateAttributeKeyType($type);
						}
	                }
	            }else{
	                \Log::addDebug("$attrDir/$f skipped, it is not a directory");
	            }
	        }
	    }else{
	        \Log::addDebug("Director does not exist or no attributes to instasll ($attrDir)");
	    }
	}
	
	private function installAttributeSetAndAssociate($pkg,$key,$setHandle,$setName,$categoryEntityHandle='collection'){	    
	    
	    
	    $service = $this->app->make('Concrete\Core\Attribute\Category\CategoryService');
	    $categoryEntity = $service->getByHandle($categoryEntityHandle);
	    $category = $categoryEntity->getController();
	    $setManager=$category->getSetManager();
	    
	    //create mew attribute set
	    $factory=$this->app->make('Concrete\Core\Attribute\SetFactory');
	    $set=$factory->getByHandle($setHandle);
	    $setManager=$category->getSetManager();
	    if(!is_object($set)){
	       $set=$setManager->addSet($setHandle,$setName,$pkg);
	    }	    
	    
	    $setManager->addKey($set,$key);
	    
	    return $set;
	}
	
	
	private function installAttribute($pkg,$handle,$description,$type,$settings=null,$searchable=false,$setHandle=null,$setName=null,$categoryEntityHandle='collection'){	     
	    //check to see if type exists, if not, assume it's custom
	    $factory=$this->app->make('Concrete\Core\Attribute\TypeFactory');
	    $typeObj=$factory->getByHandle($type);
	    if(!is_object($typeObj)){
	        $typeObj=$factory->add($type,ucwords(str_replace('_',' ',$type)),$pkg);
	    }
	    
	    
	    //install attribute key
	    $service=$this->app->make('Concrete\Core\Attribute\Category\CategoryService');
	    $categoryEntity=$service->getByHandle($categoryEntityHandle); //collection makes it a page attributes
	    $category=$categoryEntity->getController();
	    $key=$category->getByHandle($handle);
	    if(!is_object($key)){
			$typeObj=$factory->getByHandle($type);
	        if($categoryEntityHandle=='collection'){	            
				$key=new PageKey();
				$key->setAttributeKeyHandle($handle);
				$key->setAttributeKeyName($description);
				$key->setIsAttributeKeySearchable($searchable);	        
				$key=$category->add($type,$key,$settings,$pkg);
				//$category->associateAttributeKeyType($typeObj);
			}elseif($categoryEntityHandle=='user'){				
				$key=new UserKey();
				$key->setAttributeKeyHandle($handle);
				$key->setAttributeKeyName($description);	
				$key->setIsAttributeKeySearchable($searchable);
				$key->setAttributeKeyDisplayedOnMemberList(true);
				$key->setAttributeKeyEditableOnProfile(true);
				$key->setAttributeKeyDisplayedOnProfile(true);
				$key->setAttributeKeyRequiredOnProfile(false);					
				//$key=$category->add($type,[
				//    'akHandle'=>$handle,
				//    'akName'=>$description,
				//    'akIsSearchable'=>$searchable
				//],$pkg);
				$category->add($type,$key,null,$pkg);
				
				//$category->associateAttributeKeyType($typeObj);	
			}elseif($categoryEntityHandle=='store_product'){
				
				$key=new \Concrete\Package\CommunityStore\Attribute\ProductKey();
				$key->setAttributeKeyHandle($handle);
				$key->setAttributeKeyName($description);
				$key->setIsAttributeKeySearchable($searchable);	        
				$key=$category->add($type,$key,$settings,$pkg);
			}elseif($categoryEntityHandle=='event'){
				$key=new EventKey();
				$key->setAttributeKeyHandle($handle);
				$key->setAttributeKeyName($description);
				$key->setIsAttributeKeySearchable($searchable);	        
				$key=$category->add($type,$key,$settings,$pkg);
			}
	    }
	    	    
	    //create set if needed
	    if($setHandle!==null AND strlen($setHandle)>0){
	        $set=$this->installAttributeSetAndAssociate($pkg,$key,$setHandle,$setName,$categoryEntityHandle);
	    }
	    
	    
	    return $key;
	}
	
	/**
     * Configure the autoloader
     */
	private function setupAutoloader()
    {
        if (file_exists($this->getPackagePath() . '/vendor')) {
            require_once $this->getPackagePath() . '/vendor/autoload.php';
        }
    }

}
?>