<?php 
class WCPQuestion extends Question {

     /**
     * @inheritdoc
     * @return User
     */
    public static function model($class = __CLASS__)
    {
        /** @var self $model */
        $model = parent::model($class);
        return $model;
    }

    /**
     * Returns either teh correct sgqa depending on the questiontype
     * @return string The sgqa
     */
    public function getSGQA()
    {
        if($this->parent_qid != 0) {
            return "{$this->sid}X{$this->gid}X{$this->parent_qid}{$this->title}";
        }

        return $questionBaseSGQA = "{$this->sid}X{$this->gid}X{$this->qid}";
    }

    public function getWordsArray( $wordCount=0,  $unwantedWords = ['und','als','oder','and','if','or','a','is','to','I'] ) {
        $aWords = [];        
        $aUncountedWords = [];
        $aResponses = $this->getResponses();
        array_walk($aResponses, function($response) use (&$aUncountedWords, $unwantedWords) {
            $removedPunctuationAndSpecials = preg_replace("/[^a-zA-Z0-9äöüßÄÖÜ\s'-]/",'',$response);
            $removedUnwantedWords = preg_replace('/('.join('|',$unwantedWords).')\s/','',$removedPunctuationAndSpecials);
            $splittedOnWhitespace = preg_split('/\s/',$removedUnwantedWords);
            $cleansedWordList = array_map(function($word){ return mb_convert_encoding($word, 'UTF-8', 'UTF-8'); }, $splittedOnWhitespace);
            $removedOnlyWhitespace = array_filter($cleansedWordList, function($word){
                return strlen($word)>3;
            });
            $aUncountedWords = array_merge($aUncountedWords, $removedOnlyWhitespace);
        });

        $aWords = array_count_values($aUncountedWords);

        uasort($aWords, function($a,$b){
           return ($a < $b ? 1 : ($a > $b ? -1 : 0)); 
        });

        if($wordCount == 0 ) {
            $wordCount = 500;
        }
        if(count($aWords) > $wordCount ) {
            return array_slice($aWords,0,($wordCount+1));
        }

        return $aWords;
    }

    private function getResponses() {
        $oDB = Yii::app()->db;
        $oSurveyDynamic = SurveyDynamic::model($this->sid);
        $oCommand = $oDB->createCommand()->select($this->SGQA)->from($oSurveyDynamic->tableName());
        $aResponses = $oCommand->queryColumn();
        return $aResponses;
    }
}