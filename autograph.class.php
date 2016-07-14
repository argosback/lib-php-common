<?php
require_once "jpgraph/src/jpgraph.php";
require_once "jpgraph/src/jpgraph_line.php";

class AutoGraph {
  public static function columnizeData($data) {
    $columns = array_keys($data[0]);
    
    foreach($data as $datum) {
      foreach ($columns as $column) {
        $values[$column][] = $datum[$column];
      }
    }
    
    return $values;
  }
  
  
  public static function makeGraph($data, $options=array()) {
    $ctrows = count($data);
    $data = self::columnizeData($data);
    $width = Common::myOr($options['width'], 800);
    $height = Common::myOr($options['height'], 500);
    
    $graph = new Graph($width, $height, "auto");
    $graph->SetScale("textlin");
    $graph->title->Align('center', 'top', 'center');
    $graph->title->SetFont(FF_COMIC,FS_NORMAL,14);
    if ($options['title']) { 
      $graph->title->Set($options['title']);
      $graph->title->Align('center', 'top', 'center');
    }
    
    if (count($data) >= 3) {
      $graph->legend->Pos(0, 0, "right", "top");
      $graph->legend->SetFont(FF_FONT1,FS_BOLD);
    } else {
      $graph->legend->Hide();
    }
    
    $graph->xaxis->SetTickLabels($data['x']);
    $graph->xaxis->SetTextTickInterval(intval($ctrows / 10));
    $graph->xaxis->HideFirstTickLabel();
    $graph->xaxis->HideLastTickLabel();
    $graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,6);
    $graph->xaxis->SetLabelAngle(45);
    
    $colors = array('black', 'blue', 'orange', 'pink'); 
    
    foreach ($data as $name => $column) {
      if ($name == 'x') continue;
      $plot = new LinePlot($column);
      $plot->SetLegend($name);
      $plot->setColor(array_shift($colors));
      $graph->Add($plot);
    }
    
    if ($options['save']) {
      $graph->Stroke($options['save']);
    }
    
    return $graph;
  }
}

?>
