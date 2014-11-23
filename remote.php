<?php
require_once 'conf.php';

class ColumnHelper
{
    public static function isValidColumn($dataIndx)
    {            
        if (preg_match('/^[a-z,A-Z]*$/', $dataIndx))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}
class FilterHelper
{
    public static function deSerializeFilter($pq_filter)        
    {
        $filterObj = json_decode($pq_filter);
         
        $mode = $filterObj->mode;                
        $filters = $filterObj->data;
         
        $fc = array();
        $param= array();
 
        foreach ($filters as $filter)
        {            
            $dataIndx = $filter->dataIndx;            
            if (ColumnHelper::isValidColumn($dataIndx) == false)
            {
                throw new Exception("Invalid column name");
            }
            $text = $filter->value;
            $condition = $filter->condition;
             
            if ($condition == "contain")
            {
                $fc[] = $dataIndx . " like CONCAT('%', ?, '%')";
                $param[] = $text;
            }
            else if ($condition == "notcontain")
            {
                $fc[] = $dataIndx . " not like CONCAT('%', ?, '%')";
                $param[] = $text;                
            }
            else if ($condition == "begin")
            {
                $fc[] = $dataIndx . " like CONCAT( ?, '%')";
                $param[] = $text;                                
            }
            else if ($condition == "end")
            {
                $fc[] = $dataIndx . " like CONCAT('%', ?)";
                $param[] = $text;                                
            }
            else if ($condition == "equal")
            {
                $fc[] = $dataIndx . " = ?";
                $param[] = $text;                                
            }
            else if ($condition == "notequal")
            {
                $fc[] = $dataIndx . " != ?";
                $param[] = $text;                                
            }
            else if ($condition == "empty")
            {             
                $fc[] = "ifnull(" . $dataIndx . ",'')=''";                
            }
            else if ($condition == "notempty")
            {
                $fc[] = "ifnull(" . $dataIndx . ",'')!=''";                
            }
            else if ($condition == "less")
            {
                $fc[] = $dataIndx . " < ?";
                $param[] = $text;                                                
            }
            else if ($condition == "great")
            {
                $fc[] = $dataIndx . " > ?";
                $param[] = $text;                                                                
            }
        }
        $query = "";
        if (sizeof($filters) > 0)
        {
            $query = " where " . join(" ".$mode." ", $fc);
        }
 
        $ds = new stdClass();
        $ds->query = $query;
        $ds->param = $param;
        return $ds;
    }
}//end of class
 
 
//orders.php
$filterQuery = "";
$filterParam = array();
if ( isset($_GET["pq_filter"]))
{
    $pq_filter = $_GET["pq_filter"];
    $dsf = FilterHelper::deSerializeFilter($pq_filter);
    $filterQuery = $dsf->query;
    $filterParam = $dsf->param;
}    
 
$sql = "Select * from ct ".$filterQuery;
 
$dsn = 'mysql:host='.DB_HOSTNAME.';dbname='.DB_NAME;
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
); 
$dbh = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
 
$stmt = $dbh->prepare($sql);
 
$stmt->execute($filterParam);
 
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
 
echo "{\"data\":". json_encode( $rows ) ." }" ;
    
?>
