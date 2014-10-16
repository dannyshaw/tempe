<?php
namespace Tempe\Ext;

class Lang
{
    public $allowUnsetKeys = true;

    function __construct($options=[])
    {
        if (isset($options['allowUnsetKeys'])) {
            $this->allowUnsetKeys = $options['allowUnsetKeys'] == true;
            unset($options['allowUnsetKeys']);
        }

        $blocks = ['if'=>true, 'not'=>true, 'each'=>true, 'grab'=>true, 'push'=>true];
        if (isset($options['blocks'])) {
            if ($options['blocks']) {
                $blocks = $options['blocks'] + $blocks;
            }
            else {
                foreach ($blocks as &$v) $v = false;
            }
            unset($options['blocks']);
        }

        $this->blockHandlers = [];
        
        if ($blocks['if']) {
            $id = $blocks['if'] === true ? 'if' : $blocks['if'];
            $this->blockHandlers[$id] = function(&$scope, $key, $renderer, $node) {
                if (isset($scope[$key]) && $scope[$key])
                    return $renderer->renderTree($node, $scope);
            };
        }

        if ($blocks['not']) {
            $id = $blocks['not'] === true ? 'not' : $blocks['not'];
            $this->blockHandlers[$id] = function(&$scope, $key, $renderer, $node) {
                if (!isset($scope[$key]) || !$scope[$key])
                    return $renderer->renderTree($node, $scope);
            };
        }

        if ($blocks['each']) {
            $id = $blocks['each'] === true ? 'each' : $blocks['each'];
            $this->blockHandlers[$id] = function(&$scope, $key, $renderer, $node) {
                if (!isset($scope[$key])) {
                    if (!$this->allowUnsetKeys)
                        throw new \Tempe\RenderException("Unknown variable $key");
                    else
                        return;
                }

                $out = '';
                $idx = 0;
                foreach ($scope[$key] as $key=>$item) {
                    $kv = ['@key'=>$key, '@value'=>$item, '@first'=>$idx == 0, '@idx'=>$idx, '@num'=>$idx+1];
                    $curScope = is_array($item) ? array_merge($scope, $item, $kv) : $kv;
                    $out .= $renderer->renderTree($node, $curScope);
                    $idx++;
                }
                return $out;
            };
        }

        if ($blocks['grab']) {
            $id = $blocks['grab'] === true ? 'grab' : $blocks['grab'];
            $this->blockHandlers[$id] = function(&$scope, $key, $renderer, $node) {
                $out = $renderer->renderTree($node, $scope);
                if ($key)
                    $scope[$key] = $out;
                else
                    return $out;
            };
        }

        if ($blocks['push']) {
            $id = $blocks['push'] === true ? 'push' : $blocks['push'];
            $this->blockHandlers[$id] = function(&$scope, $key, $renderer, $node) {
                if (!isset($scope[$key])) {
                    if (!$this->allowUnsetKeys)
                        throw new \Tempe\RenderException("Unknown variable $key");
                    else
                        return;
                }

                $newScope = $scope;
                $item = $scope[$key];
                $newScope = $item + $newScope;
                return $renderer->renderTree($node, $newScope);
            };
        }

        $this->valueHandlers = [
            'v'=>function(&$scope, $key) {
                if (isset($scope[$key]))
                    return $scope[$key];
                elseif (!$this->allowUnsetKeys)
                    throw new \Tempe\RenderException("Unknown variable $key");
            },
        ];

        if ($options) {
            throw new \InvalidArgumentException("Unknown options: ".implode(', ', array_keys($options)));
        }
    }
}
