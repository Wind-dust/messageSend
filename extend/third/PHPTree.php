<?php

namespace third;
class PHPTree {
    private $pk = 'uid';
    private $pid = 'pid';
    private $child = '_child';
    private $root = 0;
    private $list;
    private $tree = [];

    public function __construct(array $list) {
        $this->list = $list;
    }

    public function setParam($key, $val) {
        $this->$key = $val;
    }

    private function buildTree() {
        $tree  = [];// 创建Tree
        $list  = $this->list;
        $refer = array();// 创建基于主键的数组引用
        foreach ($list as $key => $data) {
            $refer[$data[$this->pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$this->pid];// 判断是否存在parent
            //unset($list[$key][$this->pid]);
            if ($this->root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent                 =& $refer[$parentId];
                    $parent[$this->child][] =& $list[$key];
                }
            }
        }
        $this->tree = $tree;
    }

    public function listTree() {
        $this->buildTree();
        return $this->tree;
    }
}