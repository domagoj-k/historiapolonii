<?php

ArtxLoadClass('Artx_Data_Mapper');

class Artx_Data_ModuleMapper extends Artx_Data_Mapper
{
    function Artx_Data_ModuleMapper()
    {
        parent::Artx_Data_Mapper('module', 'modules', 'id');
    }

    function & find($filter = array())
    {
        $where = array();
        if (isset($filter['title']))
            $where[] = 'title = ' . $this->_db->Quote($this->_db->getEscaped($filter['title'], true), false);
        if (isset($filter['scope']) && ('site' == $filter['scope'] || 'administrator' == $filter['scope']))
            $where[] = 'client_id = ' . ('site' == $filter['scope'] ? '0' : '1');
        $result = & $this->_loadObjects($where, isset($filter['limit']) ? (int)$filter['limit'] : 0);
        return $result;
    }

    function & fetch($id)
    {
        $result = & parent::fetch($id);
        return $result;
    }

    function delete($id)
    {
        $status = $this->enableOn($id, array());
        if (is_string($status))
            return $status;
        return parent::delete($id);
    }

    function & create()
    {
        $row = & $this->_create();
        $row->published = '1';
        return $row;
    }

    function enableOn($id, $items)
    {
        $query = 'DELETE FROM #__modules_menu WHERE moduleid = ' . $this->_db->Quote($id);
        $this->_db->setQuery($query);
        $this->_db->query();
        if ($this->_db->getErrorNum())
            return $this->_error($this->_db->stderr(), 1);
        foreach ($items as $i) {
            $query = 'INSERT INTO #__modules_menu (moduleid, menuid) VALUES ('
                . $this->_db->Quote($id) . ',' . $this->_db->Quote($i) . ')';
            $this->_db->setQuery($query);
            $this->_db->query();
            if ($this->_db->getErrorNum())
                return $this->_error($this->_db->stderr(), 1);
        }
        return null;
    }

    function enabledOn($id)
    {
        $query = 'SELECT menuid FROM #__modules_menu WHERE moduleid = ' . $this->_db->Quote($id);
        $this->_db->setQuery($query);
        $this->_db->query();
        $rows = $this->_db->loadResultArray();
        if ($this->_db->getErrorNum())
            return $this->_error($this->_db->stderr(), 1);
        return $rows;
    }
}
