<?php
namespace dokku_alt;
class Model_App extends  \SQL_Model {
    public $table='app';

    function init(){
        parent::init();

        $this->hasOne('dokku_alt/Host');
        $this->addField('name');
        $this->addField('url');
        $this->addField('is_started')->type('boolean');
        $this->addField('is_enabled')->type('boolean')->defaultValue(true);

        $this->hasOne('dokku_alt/Buildpack','buildpack_url',false,'Buildpack');

        $this->addHook('beforeSave,beforeInsert,afterSave',$this);
        //$this->addHook('afterSave',$this);
//        $this->addHook('afterInsert',$this);


        $this->hasMany('dokku_alt/Config',null,null,'Config');
        $this->hasMany('dokku_alt/Domain',null,null,'Domain');
        $this->hasMany('dokku_alt/DB_Link',null,null,'DB_Link');
        $this->hasMany('dokku_alt/Access_Deploy',null,null,'Access');
    }

    function beforeSave(){
        if(!$this->id)return;
        if($this->isDirty('is_started')){
            if($this['is_started']){
                $this->start();
            }else{
                $this->stop();
            }
        }
        if($this->isDirty('is_enabled')){
            if($this['is_enabled']){
                $this->enable();
            }else{
                $this->disable();
            }
        }


        if($this['is_started']===null){
            $this['is_started'] = explode(' ',$this->cmd('status'))[2] == 'running.';
        }
        if(!$this['url']){
            $this['url'] = $this->getURL();
        }
    }
    function beforeInsert(){
        $this->ref('host_id')->executeCommand('create',[$this['name']]);
    }
    function afterSave(){
        $this->ref('Config')->tryLoadBy('name','BUILDPACK_URL')->set(['name'=>'BUILDPACK_URL', 'value'=>$this['buildpack_url'] ]) ->save();
    }
    function discover(){
        $this['is_started']=null;
        $this['url']=null;
        $this->save();
    }

    function cmd($command, $args=[]){
        array_unshift($args, $this['name']);
        return $this->ref('host_id')->executeCommand('apps:'.$command, $args);
    }

    function top(){
        return $this->cmd('top');
    }
    function disable(){
        $ret = $this->cmd('disable');
        return $ret;
    }
    function enable(){
        $ret = $this->cmd('enable');
        return $ret;
    }
    function start(){
        $ret = $this->cmd('start');
        return $ret;
    }
    function stop(){
        $ret = $this->cmd('stop');
        return $ret;
    }
    function getURL(){
        return $this->ref('host_id')->executeCommand('url', [$this['name']]);
    }
}
