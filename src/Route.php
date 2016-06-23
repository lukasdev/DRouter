<?php
namespace DRouter;

class Route
{
    protected $pattern;
    protected $callable;
    protected $conditions = array();
    protected $params = array();

    public function __construct($pattern, $callable, array $conditions)
    {
        $this->pattern = $pattern;
        $this->callable = $callable;
        $this->conditions = $conditions;
    }

    public function getCallable()
    {
        return $this->callable;
    }
    
    public function getParams()
    {
        return $this->params;
    }

    public function getPattern()
    {
        return $this->pattern;
    }

    public function match($resourceUri)
    {        
        preg_match_all('@:([\w]+)@', $this->pattern, $paramNames, PREG_PATTERN_ORDER);
        $paramNames = $paramNames[0];

        $patternAsRegex = preg_replace_callback('@:[\w]+@', [$this, 'convertToRegex'], $this->pattern);
        if ( substr($this->pattern, -1) === '/' ) {
            $patternAsRegex = $patternAsRegex . '?';
        }
        $patternAsRegex = '@^' . $patternAsRegex . '$@';

        if ( preg_match($patternAsRegex, $resourceUri, $paramValues) ) {
            array_shift($paramValues);
            
            if(count($paramValues) > 0){
                foreach ( $paramNames as $index => $value ) {
                    $this->params[substr($value, 1)] = urldecode($paramValues[$index]);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    public function convertToRegex($matches)
    {
        $key = str_replace(':', '', $matches[0]);
        if ( array_key_exists($key, $this->conditions) ) {
            return '(' . $this->conditions[$key] . ')';
        } else {
            return '([a-zA-Z0-9_\-\.]+)';
        }
    }
}