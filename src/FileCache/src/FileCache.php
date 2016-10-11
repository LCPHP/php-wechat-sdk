<?php
/**
 * php 文件缓存类
 */
// namespace niklaslu;

class fileCache{
    
    protected $filePath;
    
    public function __construct($filePath = '')
    {   
        $filePath = $filePath ? $filePath : dirname(dirname(__FILE__)).'/cache/';
        
        $this->filePath = $filePath;
    }
    /**
     * 获得缓存数据
     * @param $key 缓存名称
     * @return mixed
     */
    public function get($key)
    {
        $fileName = $this->getFileName($key);
        return $this->getFileData($fileName);
    }
    /**
     * 设置缓存
     * @param $key  缓存名称
     * @param $value    缓存值
     * @param $expire   有效时间
     * @return mixed
     */
    public function set($key, $value, $expire = 0)
    {
        $fileName = $this->getFileName($key);
        if (is_file($fileName)) {
            unlink($fileName);
        }
        $data = ['data' => $value, 'expire' => $expire];
        if (file_put_contents($fileName, json_encode($data))) {
            return true;
        }
        return false;
    }
    /**
     * 是否存在某个缓存
     * @param $key  缓存名称
     * @return mixed
     */
    public function isHave($key)
    {
        if (! is_null($this->get($key))) {
            return true;
        }
        return false;
    }
    /**
     * 删除缓存
     * @param $key  缓存名称
     * @return mixed
     */
    public function delete($key)
    {
        $fileName = $this->getFileName($key);
        if (is_file($fileName)) {
            unlink($fileName);
            return true;
        }
        return false;
    }
    /**
     * 清空所有缓存
     * @return mixed
     */
    public function flush()
    {
        $list = glob($this->filePath . '*');
        foreach ($list as $file) {
            unlink($file);
        }
        return true;
    }
    /**
     * 获得文件名称
     * @param $key
     * @return string
     */
    private function getFileName($key)
    {
        return $this->filePath . md5($key);
    }
    /**
     * 获得文件内容
     * @param $fileName
     */
    private function getFileData($fileName)
    {
        if (! is_file($fileName)) {
            return null;
        }
        $createTime = filectime($fileName);
        $data = json_decode(file_get_contents($fileName), true);
        //判断是否过期
        if ($data['expire'] != '0' && ($data['expire'] + $createTime) < time()) {
            unlink($fileName);
            return null;
        }
        return $data['data'];
    }
    
}