<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\TextQualitativeReports;
use App\Exports\TextQualitativeExport;
use App\Msttextproject;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

use function PHPSTORM_META\type;

class RetrieveAllReviewsController extends Controller
{
    public function getAllReviews()
    {
        $desiredColumns = [
            "textName",
            "anchorText",
            "abridgedText",
            "authorText",
            "gradeName",
            "unit",
            "genre1",
            "genre2",
            "textClassification",
            "elementaryOrSecondary",
            "domain",
            "topic",
            "subTopic",
            "textType",
            "project_name",
            "reviewersName",
            "reviewerStatus",
            "dateOfReview",
            "emotionqualitative",
            "clarityqualitative",
            "eternalquestionsqualitative",
            "contentknowledgequalitative",
            "currentprominencequalitative",
            "fictionoverallqualityscorequalitative",
            "emotionartsqualitative",
            "techniqueartsqualitative",
            "eternalquestionsartsqualitative",
            "contentknowledgeartsqualitative",
            "currentprominenceartsqualitative",
            "qsarts",
            "languagesacredqualitative",
            "eternalquestionssacredqualitative",
            "contentknowledgesacredqualitative",
            "sacredtextsoverallqualityscorequalitative",
            "accuracyqualitative",
            "sourcequalitative",
            "nonfictioncontentknowledgequalitative",
            "languanenonfictionqualitative",
            "overallqsqualitative",
            "emotionliterary",
            "languageliterary",
            "eternalqueationsliterary",
            "contentliterary",
            "accuracyliterary",
            "qsliterary",
            "languagederivativesacredqualitative",
            "eternalquestionsderivativesacredqualitative",
            "contentknowledgederivativesacredqualitative",
            "derivativesacredtextsoverallqualityscorequalitative",
            "secondarysourcescontentknowledgequalitative",
            "languanesecondarysourcesqualitative",
            "universalquestionsqualitative",
            "primarysourcesoverallqualityscorequalitative",
            "emotion2qualitative",
            "authenticityqualitative",
            "universalquestions2qualitative",
            "contentknowledge2qualitative",
            "currentprominence2qualitative",
            "primarysources2overallqualityscorequalitative",
            "qualityscorepercent",
            "notes",
        ];
        // $allreview = DB::table('jhu_knowledgemap.allreviews')->get()->toArray();
        // dd($allreview);
        $reports = $this->getAllReviewsDBSql();
        // dd($header);
        // $d = DB::table('allreviews')->insert($reports);
        // dd();
        $jsonReport = $reports->get();
        // dd($jsonReport);
        // foreach ($jsonReport as $report) {
        //     $dataArray = (array) $report;
        
        //     // Remove any columns that don't exist in the "allreviews" table
        //     $filteredDataArray = array_intersect_key($dataArray, array_flip($desiredColumns));
        
        //     DB::table('allreviews')->insert($filteredDataArray);
        // }
        foreach ($jsonReport as $report) {
            $toInsert = (array)$report;
            echo "<pre>";
            print_r($toInsert);
            echo "</pre>";
            DB::table('jhu_knowledgemap.allreviews')->insert($toInsert);

        }
        // $filePath = storage_path('app/reviews.json');
        // $jsonData = json_encode($jsonReport, JSON_PRETTY_PRINT);
        // File::put($filePath, $jsonData);
        echo "asdfa";
        dd();
        $formatReport = $jsonReport;
        $type = getKnwoledgeMap();
        return view('testview')
            ->with('reports', $reports)
            ->with('formatReport', $formatReport)
            ->with('header', $header);
    }

    public function getAllReviewsDBSql()
    {
        $mstTxtType = $this->getTextTypeData();
        $dbsql = DB::table('lk_msttext_project');
        $dbSql = $dbsql->join('mst_text as t', 'lk_msttext_project.text_id', '=', 't.id');
        $dbSql = $dbSql->join('project as p', 'lk_msttext_project.project_id', '=', 'p.id')
            ->leftJoin('jhu_shared.grade_level as gl', 'lk_msttext_project.grade_level', '=', 'gl.id')
            ->leftJoin('mst_genre_1 as g1', 'lk_msttext_project.mstgenre1_id', '=', 'g1.id')
            ->leftJoin('mst_genre_2 as g2', 'lk_msttext_project.mstgenre2_id', '=', 'g2.id')
            ->leftJoin('mst_text_classification as tc', 'lk_msttext_project.msttestclassification_id', '=', 'tc.id')
            /* Start For Multireview table join */
            ->leftJoin('text_review as tr', function ($join) {
                $join->on('tr.text_id', '=', 'lk_msttext_project.text_id');
                $join->on('tr.project_id', '=', 'lk_msttext_project.project_id');
                $join->on('tr.lk_msttext_ref_id', '=', 'lk_msttext_project.id'); //JHU--93
                $join->where('tr.review_by', '!=', null);
            });
        $dbSql = $dbSql->leftjoin('jhu_knowledgemap.mst_review_status as rs', 'tr.mstreviewstatus_id', '=', 'rs.id');
        $dbSql = $dbSql->leftJoin('mst_text_type as tt', 'tr.msttexttype_id', '=', 'tt.id');
        $qrCols = '';
        foreach ($mstTxtType as $mtt) {
            $dbSql->leftJoin($mtt->table_name, 'tr.id', 'qr_' . $mtt->id . '.text_review_id');
            if (!empty($qrCols)) {
                $qrCols = $qrCols . ',';
            }
            $qrCols = $qrCols . $mtt->select_cols . ',' . $mtt->quality_measure_sum_alias;
        } 
        // dd($qrCols);    
        /* End */
        // $sql = DB::raw($qrCols);
        // dd($sql);
        $dbSql->leftJoin('jhu_sso.user as u', 'tr.review_by', '=', 'u.id')
            ->leftJoin('lk_project_gradelevel_tagtype as pgltt', function ($join) {
                $join->on('lk_msttext_project.project_id', '=', 'pgltt.project_id');
                $join->on('lk_msttext_project.grade_level', '=', 'pgltt.gradelevel_id');
            }); //dd($qrCols);
        $dbSql = $dbSql->select(
            't.name AS textName', DB::raw('public.f_if(lk_msttext_project.is_anchor_text = 1, \'Yes\', \'No\') AS "anchorText" '), DB::raw('public.f_if(tr.abridged = 1, \'Yes\', \'No\') AS "abridgedText" '),
            't.author AS authorText',
            'gl.name AS gradeName',
            'lk_msttext_project.unit AS unit',
            'g1.name AS genre1',
            'g2.name AS genre2',
            'tc.name AS textClassification', DB::raw('public.f_if(pgltt.msttagtype_name IS NULL, gl.tag_type, pgltt.msttagtype_name) AS "elementaryOrSecondary" '),
            'v.domain_name as domain',
            'v.topic_name as topic',
            'v.sub_topic_name as subTopic',
            'tt.name AS textType',
            'p.name AS project_name', DB::raw('CONCAT(u.first_name, \' \', u.last_name) AS "reviewersName" '),
            'rs.name  as reviewerStatus', DB::raw('public.f_date_format(lk_msttext_project.review_date, \'%m/%d/%Y \' )  AS "dateOfReview" '), DB::raw($qrCols), DB::raw('tr.qualitative_measures as qualityScorePercent'),
            'tr.notes'
        );

        $dbSql = $dbSql->join('jhu_knowledgemap.lk_text_project_tag_normalised as v', function ($join) {
            $join->on('tr.lk_msttext_ref_id', '=', 'v.lk_msttext_ref_id');
            $join->on('tr.review_by', '=', 'v.review_by');
        });




        // $dbSql = $dbSql->whereIn('t.id', [,]);
        $dbSql = $dbSql->whereIn('p.id', [85]);
        $dbSql = $dbSql->where('p.is_historic', 1);
        $dbSql = $dbSql->where('p.knowledge_map_id', 2);
        $dbSql = $dbSql->where('tr.is_archived', 0);
        $dbSql = $dbSql->where('lk_msttext_project.is_archived', 0);
        $dbSql = $dbSql->where('tr.is_hide', 0);
        $dbSql = $dbSql->where('lk_msttext_project.is_hide', 0);
        $dbSql = $dbSql->where('tt.id', '!=', 7);
        $dbSql = $dbSql->where('tt.id', '!=', 8);
        // // dd($dbsql);
        // $dbsql = $dbsql->get();
        // dd($dbsql);
        return $dbsql;

    }

    function getTextType()
    {
        try {
            $textType = \DB::table('mst_text_type')
                ->select('header_cols')
                ->where('is_archived', '=', 0)
                ->where('table_name', '!=', '')
                ->where('select_cols', '!=', '')
                ->where('potential_score', '!=', 0)
                ->where('id', '!=', 7)
                ->where('id', '!=', 8)
                ->where('ui_template', '!=', '')
                ->where('quality_measure_sum_alias', '!=', '')
                ->where('header_cols', '!=', '')
                ->pluck('header_cols')->all();
            return formatOutput(1, 'success', $textType);
        } catch (\Exception $ex) {
            return formatOutput(0, 'Error', '', $ex);
        }
    }

    function getRubricHeadings()
    {
        $textType = $this->getTextType();
        $data = $textType->getData();
        $arr = array();
        if ($data->status == 1) {
            $myArray = json_decode(json_encode($data->data), true);
            ksort($myArray);

            foreach ($myArray as $dt) {
                $str = $dt;
                $exp = explode(';', $str);
                //array_push($arr, $exp); 
                foreach ($exp as $ex) {
                    $arr[] = $ex;
                }
            }
        }
        return $arr;
    }


    function getTextTypeData(){
        $list= DB::table('mst_text_type');
        $mstTxtType=$list->where('is_archived','!=',1)
                    ->where('table_name', '!=',"")
                    ->where('select_cols', '!=',"")
                    ->where('potential_score', '!=',0)
                    ->where('potential_score', '!=',null)
                    ->where('id', '!=',7)
                    ->where('id', '!=',8)
                    ->where('ui_template', '!=',"")
                    ->where('quality_measure_sum_alias', '!=',"")
                    ->where('header_cols', '!=',"")
    
                    ->select('id','table_name','select_cols','quality_measure_sum_alias')
                    ->get();//dd($mstTxtType);
       // dd("kjkj");
           return $mstTxtType;
    }

    function getKnwoledgeMap(){
        // $type = getList('mst_knowledge_map', 'id', '', '', 0)->toArr;
        $type = DB::table('mst_knowledge_map')->pluck('id');
        return $type;
    }
}