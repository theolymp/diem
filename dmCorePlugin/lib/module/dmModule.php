<?php

class dmModule extends dmMicroCache
{

  protected
    $key,
    $space,
    $manager,
    $options;

  public function __construct($key, dmModuleSpace $space, dmModuleManager $manager, array $options)
  {
    $this->key    = $key;
    $this->space  = $space;
    $this->manager = $manager;

    $this->initialize($options);
  }
  
  protected function initialize(array $options)
  {
    $this->options = $options;
  }

  public function getSpace()
  {
    return $this->space;
  }

  public function isProject()
  {
    return $this instanceof dmProjectModule;
  }


  public function hasAdmin()
  {
    return $this->options['admin'];
  }

  public function __toString()
  {
    return $this->key;
  }

  public function toDebug()
  {
    return $this->toArray();
  }

  public function getKey()
  {
    return $this->key;
  }

  public function getParam($key)
  {
    return isset($this->options[$key]) ? $this->options[$key] : null;
  }

  public function setParam($key, $value)
  {
    return $this->options[$key] = $value;
  }

  public function getName()
  {
    return $this->options['name'];
  }

  public function getPlural()
  {
    return $this->options['plural'];
  }
  
  public function getCredentials()
  {
    return $this->options['credentials'];
  }

  public function getModel()
  {
    return $this->options['model'];
  }

  public function hasModel()
  {
    return false !== $this->options['model'];
  }

  public function hasPage()
  {
    return false;
  }

  public function getUnderscore()
  {
    return $this->options['underscore'];
  }

  public function getSlug()
  {
    if ($this->hasCache('slug'))
    {
      return $this->getCache('slug');
    }

    return $this->setCache('slug', dmString::slugify(dm::getI18n()->__($this->getPlural())));
  }

  public function getCompleteSlug()
  {
    if($this->hasCache('complete_slug'))
    {
      return $this->getCache('complete_slug');
    }

    return $this->setCache('complete_slug',
      implode('/', array(
        $this->getSpace()->getType()->getSlug(),
        $this->getSpace()->getSlug(),
        $this->getSlug()
      ))
    );
  }

  /*
   * Full system path to the symfony module directory
   * @return string|null /path/to/the/module
   */
//  public function getDir()
//  {
//    if($this->hasCache('dir'))
//    {
//      return $this->getCache('dir');
//    }
//
//    $dirs = dmContext::getInstance()->getConfiguration()->getControllerDirs($this->key);
//
//    $dir = null;
//    foreach($dirs as $actionPath => $isProject)
//    {
//      if(file_exists($actionPath))
//      {
//        $dir = preg_replace('|^(.+)/actions$|', '$1', $actionPath);
//        break;
//      }
//    }
//    
//    return $this->setCache('dir', $dir);
//  }

  public function getTable()
  {
    if ($this->hasCache('table'))
    {
      return $this->getCache('table');
    }

    return $this->setCache('table', $this->hasModel() ? dmDb::table($this->options['model']) : false);
  }

  public function getForeigns()
  {
    throw new dmException('deprecated?');
    if ($this->hasCache('foreigns'))
    {
      return $this->getCache('foreigns');
    }

    $foreigns = array();
    foreach($this->getTable()->getRelationHolder()->getForeigns() as $relation)
    {
      if ($foreignModule = $this->manager->getModuleOrNull($relation->getClass()))
      {
        $foreigns[$foreignModule->getKey()] = $foreignModule;
      }
    }

    return $this->setCache('foreigns', $foreigns);
  }

  public function getForeign($foreignModuleKey)
  {
    if ($foreignModule = $this->manager->getModuleOrNull($foreignModuleKey))
    {
      if ($this->hasForeign($foreignModule))
      {
        return $foreignModule;
      }
    }
    return null;
  }

  public function hasForeign($something)
  {
    if ($foreignModule = $this->manager->getModuleOrNull($something))
    {
      return array_key_exists($foreignModule->getKey(), $this->getForeigns());
    }
    return false;
  }

  public function getLocals()
  {
    if ($this->hasCache('locals'))
    {
      return $this->getCache('locals');
    }

    $locals = array();
    foreach($this->getTable()->getRelationHolder()->getLocals() as $relation)
    {
      if($localModule = $this->manager->getModuleByModel($relation->getClass()))
      {
        $locals[$localModule->getKey()] = $localModule;
      }
    }

    return $this->setCache('locals', $locals);
  }

  public function getLocal($localModuleKey)
  {
    if ($localModule = $this->manager->getModuleOrNull($localModule))
    {
      if ($this->hasLocal($localModule))
      {
        return $localModule;
      }
    }
    return null;
  }

  public function hasLocal($something)
  {
    if ($localModule = $this->manager->getModuleOrNull($something))
    {
      return array_key_exists($localModule->getKey(), $this->getLocals());
    }
    return false;
  }

  public function getAssociations()
  {
    if ($this->hasCache('associations'))
    {
      return $this->getCache('associations');
    }

    $associations = array();
    foreach($this->getTable()->getRelationHolder()->getAssociations() as $key => $relation)
    {
      $associationModule = $this->manager->getModule($relation->getClass());
      $associations[$associationModule->getKey()] = $associationModule;
    }

    return $this->setCache('associations', $associations);
  }

  public function getAssociation($associationModuleKey)
  {
    if ($associationModule = $this->manager->getModuleOrNull($associationModuleKey))
    {
      if ($this->hasAssociation($associationModule))
      {
        return $associationModule;
      }
    }
    return null;
  }

  public function hasAssociation($something)
  {
    if ($associationModule = $this->manager->getModule($something))
    {
      return array_key_exists($associationModule->getKey(), $this->getAssociations());
    }
    return false;
  }

  public function toArray()
  {
    return array(
      'key' => $this->key,
      'model' => $this->options['model'],
      'options' => $this->options
    );
  }

  public function is($something)
  {
    if (is_string($something))
    {
      return $this->key == dmString::modulize($something);
    }
    
    if($something instanceof dmModule)
    {
      return $something->getKey() === $this->key;
    }

    return false;
  }
  
  public function interactsWithPageTree()
  {
    return $this->isProject();
  }
  
  /*
   * @return dmModuleManager
   */
  public function getManager()
  {
    return $this->manager;
  }
}