<?php
namespace News\Mapper\Helper;

use ZfcBase\Mapper\AbstractDbMapper;

class MagicFind extends AbstractDbMapper
{
    function find($name, $arguments)
    {
        $findAll = strpos($name, 'findAll');

        if (substr($name, 0, 6) != 'findBy' && substr($name, 0, 9) != 'findAllBy' && $findAll === false) {
            throw new Exception('Invalid method ' . $name);
        }

        if (!$findAll === false) {
            $fields = explode('And', (substr($name, 0, 6) == 'findBy') ? substr($name, 6) : substr($name, 9));
        } else {
            $fields = array();
        }

        $contain = true;
        $columns = array('*');
        $order = $limit = $offset = null;

        if ($findAll === false && is_array($arguments[count($arguments) - 1]) && (count($arguments) != count($fields))) {
            $options = array_pop($arguments);
            $columns = (isset($options['columns'])) ? $options['columns'] : $columns;
            $order = (isset($options['order'])) ? $options['order'] : $order;
            $limit = (isset($options['limit'])) ? $options['limit'] : $limit;
            $offset = (isset($options['offset'])) ? $options['offset'] : $offset;
            $debug = (isset($options['detoArraybug'])) ? $options['debug'] : $debug;
        }

        if (count($arguments) != count($fields)) {
            throw new Exception('Argument count does not match field count');
        }

        $select = $this->getSelect()
            ->columns($columns);

        if (!is_null($order)) {
            $select->order(($order == null) ? $primaryKey[1] . ' ASC' : $order);
        }

        if ($findAll === false) {
            foreach ($fields as $key => $field) {
                if (substr($field, 0, 3) == 'Not') {
                    $field = substr($field, 3);
                    switch(true) {
                        case (is_null($arguments[$key])):
                            $select->where($field . ' IS NOT NULL');
                            break;
                        case (is_array($arguments[$key])):
                            $select->where->notin($field, $arguments[$key]);
                            break;
                        default:
                            $select->where->notEqualTo($field, $arguments[$key]);
                            break;
                    }
                } else {
                    switch(true) {
                        case (is_null($arguments[$key])):
                            $select->where($field . ' IS NULL');
                            break;
                        case (is_array($arguments[$key])):
                            $select->where->in($field, $arguments[$key]);
                            break;
                        default:
                            $select->where(array($field => $arguments[$key]));
                            break;
                    }
                }
            }
        }

        if ($limit) {
            $select->limit($limit, $offset);
        }

        return $this->select($select);
    }
}