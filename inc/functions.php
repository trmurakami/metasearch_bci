<?php

function monta_consulta($get_content,$skip,$limit,$date_range){

    if (!empty($date_range)){
        $get_query2[] = $date_range;
    }

    foreach ($get_content as $key => $value) {

        $conta_value = count($value);

        if ($conta_value > 1) {
            foreach ($value as $valor){
                $get_query1[] = '{"term":{"'.$key.'":"'.$valor.'"}}';
            }
        } else {
             foreach ($value as $valor){
                 $get_query2[] = '{"term":{"'.$key.'":"'.$valor.'"}}';
             }
        }
    }

    $query_part = '"must" : ['.implode(",",$get_query1).']';
    $query_part2 = implode(",",$get_query2);

    $query = '
                {
                   "sort" : [
                       { "year" : "desc" }
                   ],
                   "query" : {
                      "constant_score" : {
                         "filter" : {
                            "bool" : {
                              "should" : [
                                { "bool" : {
                                '.$query_part.'
                               }}
                              ],
                              "filter": [
                                '.$query_part2.'
                              ]
                           }
                         }
                      }
                   },
                  "from": '.$skip.',
                  "size": '.$limit.'
                }
    ';

    return $query;
}

function monta_aggregate($get_content,$date_range){

    if (!empty($date_range)){
        $get_query2[] = $date_range;
    }

    foreach ($get_content as $key => $value) {

        $conta_value = count($value);

        if ($conta_value > 1) {
            foreach ($value as $valor){
                $get_query1[] = '{"term":{"'.$key.'":"'.$valor.'"}}';
            }
        } else {
             foreach ($value as $valor){
                 $get_query2[] = '{"term":{"'.$key.'":"'.$valor.'"}}';
             }
        }
    }

    $query_part = '"must" : ['.implode(",",$get_query1).']';
    $query_part2 = implode(",",$get_query2);

    $query = '
                    "query" : {
                      "constant_score" : {
                         "filter" : {
                            "bool" : {
                              "should" : [
                                { "bool" : {
                                '.$query_part.'
                               }}
                              ],
                              "filter": [
                                '.$query_part2.'
                              ]
                           }
                         }
                      }
                   },
    ';

    return $query;
}

function consulta_elastic ($query) {
    $ch = curl_init();
    $method = "POST";
    $url = "http://localhost/rppbci/artigos/_search";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PORT, 9200);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, TRUE);
    return $data;
}

function contar_registros () {

  $query = '
  {
    "query": {
        "bool" : {
          "must_not" : {
            "term" : { "_status" : "deleted" }
          }
        }
     },
    "size": 0
  }
  ';


    $ch = curl_init();
    $method = "POST";
    $url = "http://localhost/rppbci/artigos/_count";

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_PORT, 9200);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $query);

    $result = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($result, TRUE);
    return $data["count"];
}

function ultimos_registros() {

     $query = '
     {
       "query": {
           "bool" : {
             "must_not" : {
               "term" : { "_status" : "deleted" }
             }
           }
        },
       "size": 10,
       "sort" : [
           {"_uid" : {"order" : "desc"}}
           ]
     }
     ';
    $data = consulta_elastic($query);

echo '<h3>Últimos artigos</h3>';
echo '<div class="ui divided items">';
foreach ($data["hits"]["hits"] as $r){
#print_r($r);
echo '<div class="item">
<div class="ui tiny image">';
echo '</div>';
echo '<div class="content">';
if (!empty($r["_source"]['title'])){
echo '<a class="ui small header" href="single.php?_id='.$r['_id'].'">'.$r["_source"]['title'][0].' ('.$r["_source"]['year'][0].')</a>';
};
echo '<div class="extra">';
if (!empty($r["_source"]['creator'])) {
foreach ($r["_source"]['creator'] as $autores) {
echo '<div class="ui label" style="color:black;"><i class="user icon"></i><a href="result.php?creator[]='.$autores.'">'.$autores.'</a></div>';
}
};
echo '</div></div>';
echo '</div>';
}
echo '</div>';

}

function gerar_faceta($consulta,$url,$campo,$tamanho,$nome_do_campo,$sort) {

    if (!empty($sort)){

         $sort_query = '"order" : { "_term" : "'.$sort.'" },';
        }
    $query = '
    {
        "size": 0,
        '.$consulta.'
        "aggregations": {
          "counts": {
            "terms": {
              "field": "'.$campo.'",
              "missing": "N/D",
              '.$sort_query.'
              "size":'.$tamanho.'
            }
          }
        }
     }
     ';

    $data = consulta_elastic($query);

    echo '<div class="item">';
    echo '<a class="active title"><i class="dropdown icon"></i>'.$nome_do_campo.'</a>';
    echo '<div class="content">';
    echo '<div class="ui list">';
    foreach ($data["aggregations"]["counts"]["buckets"] as $facets) {
        echo '<div class="item">';
        echo '<a href="'.$url.'&'.$campo.'[]='.$facets['key'].'">'.$facets['key'].'</a><div class="ui label">'.$facets['doc_count'].'</div>';
        echo '</div>';
    };
    echo   '</div>
      </div>
  </div>';

}


?>
