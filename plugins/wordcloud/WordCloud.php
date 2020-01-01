<?php
spl_autoload_register(function ($class_name) {
    if (preg_match("/^WCP.*/", $class_name)) {
        if (file_exists(__DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$class_name . '.php')) {
            include __DIR__.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.$class_name . '.php';
        } elseif (file_exists(__DIR__.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$class_name . '.php')) {
            include __DIR__.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$class_name . '.php';
        } elseif (file_exists(__DIR__.DIRECTORY_SEPARATOR.'helper'.DIRECTORY_SEPARATOR.$class_name . '.php')) {
            include __DIR__.DIRECTORY_SEPARATOR.'helper'.DIRECTORY_SEPARATOR.$class_name . '.php';
        }
    }
});

/**
 * Advanced Statistics plugin
 *
 * @package AdvancedStatistics
 * @author Markus Flür <markus.fluer@limesurvey.org>
 * @license GPL3.0
 */
class WordCloud extends PluginBase
{
    protected $storage = 'DbStorage';
    protected static $name = 'WordCloud';
    protected static $description = 'WordCloud visualisation for LimeSurvey';

    protected $settings = array(

        'wordCount' => array(
            'type' => 'string',
            'label' => 'The number of words shown (0 for all of them)',
            'default' => '50',
        ),
        'cloudWidth' => array(
            'type' => 'string',
            'label' => 'The width of the cloud element',
            'default' => '800',
        ),
        'cloudHeight' => array(
            'type' => 'string',
            'label' => 'the height of the cloud element',
            'default' => '500',
        ),
        'fontPadding' => array(
            'type' => 'string',
            'label' => 'The minimum space between the words',
            'default' => "5",
        ),
        'wordAngle' => array(
            'type' => 'string',
            'label' => 'The angle in which the words are randomly tilted in the map',
            'default' => '45',
        ),
        'minFontSize' => array(
            'type' => 'string',
            'label' => 'The minimum font size for a single word',
            'default' => '10',
        ),
        'badwords' => array(
            'type' => 'text',
            'label' => "Words you dont want to quantize in the wordcloud, please use exactly one space between the words",
            'default' => "und als oder and if or a is to I",
        ),
    );

    /**
     * Plugin initialisiertung
     *
     * Binded das Plugin an die entsprechenden Events im Basissystem
     *
     * @return void
     */
    public function init()
    {
        /**
         * Here you should handle subscribing to the events your plugin will handle
         */
        $this->subscribe('newDirectRequest');
        $this->subscribe('beforeActivate');
        $this->subscribe('beforeDeactivate');
        //$this->subscribe('afterModelSave');
    }

    /**
     * Reacts on incoming direct requests and relays to subsequent method
     *
     * @return void
     */
    public function newDirectRequest()
    {
        $request = $this->api->getRequest();
        $oEvent = $this->getEvent();

        if ($oEvent->get('target') !== 'WordCloud') {
            return;
        }

        $action = $oEvent->get('function');
        return call_user_func([$this, $action], $oEvent, $request);
    }

    public function beforeActivate() {
         $aMenuSettings = [
            "name" => 'wordcloud',
            "title" => 'WordCloudView',
            "menu_title" => 'WordClouds',
            "menu_description" => 'WordCloud',
            "menu_icon" => 'cloud',
            "menu_icon_type" => 'fontawesome',
            "menu_link" => 'admin/pluginhelper/sa/sidebody',
            "permission" => 'statistics',
            "permission_grade" => 'read',
            "hideOnSurveyState" => true,
            "linkExternal" => false,
            "manualParams" => ['plugin' => 'WordCloud', 'method' => 'index'],
            "pjaxed" => true,
            "addSurveyId" => true,
            "addQuestionGroupId" => false,
            "addQuestionId" => false,
        ];
        $oMenu = Surveymenu::model()->findByAttributes(['name' => 'mainmenu']);

        return SurveymenuEntries::staticAddMenuEntry($oMenu->id,$aMenuSettings);
    }

    public function beforeDeactivate() {
        $oSuerveymenuEntry = SurveymenuEntries::model()->findByAttributes(['name' => 'wordcloud']);
        return $oSuerveymenuEntry->delete();
    }


    public function index() {
        $iSurveyId = Yii::app()->request->getParam('surveyid');
        $oSurvey = Survey::model()->findByPk($iSurveyId);
        
        $aQuestions = $this->getUsableQuestions($oSurvey);

        $aData['questions'] = $aQuestions;
        $aData['pluginSettings'] = [
            "cloudWidth" => $this->get('cloudWidth',null,null,800),
            "cloudHeight" => $this->get('cloudHeight',null,null,500),
            "fontPadding" => $this->get('fontPadding',null,null,5),
            "wordAngle" => $this->get('wordAngle',null,null,45),
            "minFontSize" => $this->get('minFontSize',null,null,10),
        ];

        //WC2 Variant 
        // $this->registerScript('assets/lib/wordcloud2.js');
        // $this->registerScript('assets/build/wordcloudwc2.js');
        
        //D3 variant
        $this->registerCss('assets/css/wordcloud.css');
        $this->registerScript('assets/lib/d3.min.js');
        $this->registerScript('assets/lib/d3.layout.cloud.js');
        $this->registerScript('assets/build/wordcloudd3.js');

        return $this->renderPartial('view', $aData, true);
    }

    public function getWordCloudData($oEvent, $oRequest) {
        list($iQuestionId, $language) = explode("-",$oRequest->getParam('qid'));
        $oCloudQuestion = WCPQuestion::model()->findByAttributes(['qid' => $iQuestionId, 'language' => $language]);
    
        // if(!in_array($oCloudQuestion->type, ["Q","T","U","S"])) {
        //     throw new CHttpException('Question type not supported for word cloud');
        // }

        $badWords = $this->get('badwords',null,null,null);
        $wordCount = $this->get('wordCount',null,null,0);
        
        if($badWords!= null) {
            $badWords = explode(" ",$badWords);
        }
        
        $aWordArray = $oCloudQuestion->getWordsArray($wordCount,$badWords);
        $aData = ['data' => $aWordArray];
        return $this->renderPartial('partial.jsonresponse', $aData);
    }



    ############################################################ Private Methods ##################
    
    private function getUsableQuestions($oSurvey) {
        $oCriteria = new CDbCriteria;
        $oCriteria->compare('sid', $oSurvey->sid);
        $oCriteria->compare('parent_qid', 0);
        $oCriteria->compare('type', ["T","U","S","Q"]);

        $aQuestions = Question::model()->findAll($oCriteria);
        $aUsableQuestions = [];
        foreach($aQuestions as $oQuestion) {
            if(in_array($oQuestion->type,["T","U","S"])) {
                $aUsableQuestions[] = $oQuestion;
            }
            if($oQuestion->type == 'Q') {
                $aUsableQuestions = array_merge($aUsableQuestions, $oQuestion->subquestions);
            }
        }
        return $aUsableQuestions;
    }


    /**
     * Helperfunction to load correct scripts
     *
     * @param string $relativePathToScript
     * @param string $parentPlugin
     * @return void
     */
    protected function registerScript($relativePathToScript, $parentPlugin=null, $pos = LSYii_ClientScript::POS_BEGIN)
    {
        $parentPlugin = $parentPlugin===null ? get_class($this) : $parentPlugin;

        $scriptToRegister = null;
        if (file_exists(YiiBase::getPathOfAlias('userdir').'/plugins/'.$parentPlugin.'/'.$relativePathToScript)) {
            $scriptToRegister = Yii::app()->getAssetManager()->publish(
                YiiBase::getPathOfAlias('userdir').'/plugins/'.$parentPlugin.'/'.$relativePathToScript
            );
        } elseif (file_exists(YiiBase::getPathOfAlias('webroot').'/plugins/'.$parentPlugin.'/'.$relativePathToScript)) {
            $scriptToRegister = Yii::app()->getAssetManager()->publish(
                YiiBase::getPathOfAlias('webroot').'/plugins/'.$parentPlugin.'/'.$relativePathToScript
            );
        } elseif (file_exists(Yii::app()->getBasePath().'/core/plugins/'.$parentPlugin.'/'.$relativePathToScript)) {
            $scriptToRegister = Yii::app()->getAssetManager()->publish(
                Yii::app()->getBasePath().'/core/plugins/'.$parentPlugin.'/'.$relativePathToScript
            );
        }
        Yii::app()->getClientScript()->registerScriptFile($scriptToRegister, $pos);
    }

    /**
     * Helper function to load the correct styles
     *
     * @param string $relativePathToScript
     * @param string $parentPlugin
     * @return void
     */
    protected function registerCss($relativePathToCss, $parentPlugin=null)
    {
        $parentPlugin = $parentPlugin===null ? get_class($this) : $parentPlugin;
        $pathPossibilities = [
            YiiBase::getPathOfAlias('userdir').'/plugins/'.$parentPlugin.'/'.$relativePathToCss,
            YiiBase::getPathOfAlias('webroot').'/plugins/'.$parentPlugin.'/'.$relativePathToCss,
            Yii::app()->getBasePath().'/application/core/plugins/'.$parentPlugin.'/'.$relativePathToCss
        ];
        $cssToRegister = null;
        if (file_exists(YiiBase::getPathOfAlias('userdir').'/plugins/'.$parentPlugin.'/'.$relativePathToCss)) {
            $cssToRegister = Yii::app()->getAssetManager()->publish(
                YiiBase::getPathOfAlias('userdir').'/plugins/'.$parentPlugin.'/'.$relativePathToCss
            );
        } elseif (file_exists(YiiBase::getPathOfAlias('webroot').'/plugins/'.$parentPlugin.'/'.$relativePathToCss)) {
            $cssToRegister = Yii::app()->getAssetManager()->publish(
                YiiBase::getPathOfAlias('webroot').'/plugins/'.$parentPlugin.'/'.$relativePathToCss
            );
        } elseif (file_exists(Yii::app()->getBasePath().'/core/plugins/'.$parentPlugin.'/'.$relativePathToCss)) {
            $cssToRegister = Yii::app()->getAssetManager()->publish(
                Yii::app()->getBasePath().'/core/plugins/'.$parentPlugin.'/'.$relativePathToCss
            );
        }
        Yii::app()->getClientScript()->registerCssFile($cssToRegister);
    }

}
