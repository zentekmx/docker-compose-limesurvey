<?php
/**
 * View: Main view and question selector
 * 
 * @package WordCloud
 * @author Markus Flür <markus.fluer@limesurvey.org>
 * @license GPL3.0
 */
?>
<div class='side-body <?php echo getSideBodyClass(false); ?>'>
    <div class="container-center">
        <div class="row">
            <div class="col-sm-12">
                <h3 class="pagetitle">WordCloud</h3>
            </div>
        </div>
        <div class="row">
            <hr/>
        </div>
        <div class="row">
            <div class="ls-flex ls-flex-row align-items-center align-content-space-between">
                <div class="text-center ls-flex">
                    <button class="btn btn-default btn-block" id="WordCloud--Action--DownloadPNG">Download (PNG)</button>
                </div>
                <div class="text-center ls-flex">
                    <label for="WordCloud--ColorPicker-startColor">
                        Start color
                    </label>&nbsp;&nbsp;
                    <input id="WordCloud--ColorPicker-startColor" type="color" class="WordCloud--Action--ColorPicker" name="startColor" value="#cd113b" /> 
                </div>
                <div class="text-center ls-flex">
                    <label for="WordCloud--ColorPicker-finalColor">
                        Final color
                    </label>&nbsp;&nbsp;
                    <input id="WordCloud--ColorPicker-finalColor" type="color" class="WordCloud--Action--ColorPicker" name="finalColor" value="#213262" /> 
                </div>
                <div class="text-center ls-flex">
                    <select id="WordCloud--QuestionSelector" name="WordCloudQuestionSelector"  class="form-control">
                        <option value="---">No question selected</option>
                        <?php foreach($questions as $oQuestion) {
                            printf("<option value='%s-%s'>(%s) %s</option>",$oQuestion->qid, $oQuestion->language, $oQuestion->title, ellipsize($oQuestion->question, 40));
                        } ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <hr/>
        </div>
        <div class="row">
            <div id="WordCloud--loadingBlock" class="col-xs-12 container-center">
                <div class="loader--loaderWidget ls-flex ls-flex-column align-content-center align-items-center" style="min-height: 100%; margin-top:25px;">
                    <div class="ls-flex align-content-center align-items-center">
                        <div class="loader-wordcloud text-center">
                            <div class="contain-pulse animate-pulse">
                                <div class="square"></div>
                                <div class="square"></div>
                                <div class="square"></div>
                                <div class="square"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 text-center" id="WordCloud--imagecontainer" style="">
            </div>
        </div>
    </div>
</div>
<script>
$(document).on('ready pjax:scriptcomplete', function(){ 
    var WordCloudFactory = LS.getWordCloudProcessorFactory();
    var wordCloudRenderer = WordCloudFactory({
        getQuestionDataUrl: '<?=Yii::app()->createUrl('/plugins/direct/',['plugin' => 'WordCloud', 'function' => 'getWordCloudData'])?>',
        cloudWidth: <?=$pluginSettings['cloudWidth']?>,
        cloudHeight: <?=$pluginSettings['cloudHeight']?>,
        fontPadding: <?=$pluginSettings['fontPadding']?>,
        wordAngle: <?=$pluginSettings['wordAngle']?>,
        minFontSize: <?=$pluginSettings['minFontSize']?>
    });
    wordCloudRenderer.bind();

});
</script>






