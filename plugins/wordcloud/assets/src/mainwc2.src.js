const WordCloudProcessor = function(options){
    const questionSelector = '#WordCloud--QuestionSelector';
    const loadingBlock = '#WordCloud--loadingBlock';
    const imageContainer = '#WordCloud--imagecontainer';

    let loading = false;

    const toggleLoader = () => {
        if(loading) {
            $(loadingBlock).css('display','none');
        } else {
            $(loadingBlock).css('display','');
        }
        loading = !loading;
    };

    const drawWordCloud = (wordListObject) => {
        const wordData = LS.ld.toPairs(wordListObject);
        $(imageContainer).html('<canvas id="WordCloud--image" style="width:100%"></canvas>');
        WordCloud(document.getElementById('WordCloud--image'), { list: words });
    };


    const reloadQuestionData = () => {
        let currentQid = $(questionSelector).val();
        toggleLoader();
        $.ajax({
            url: options.getQuestionDataUrl,
            data: {qid: currentQid},
            success: drawWordCloud,
            error: (err,xhr) => {console.error("WordCloudError => ", err); toggleLoader();}
        });
    }

    const bind = () => {
        $(questionSelector).on('change', reloadQuestionData)
    }

    return {
        bind,
        reloadQuestionData,
    };
};

window.LS.getWordCloudProcessorFactory = function(){
    return WordCloudProcessor;
};