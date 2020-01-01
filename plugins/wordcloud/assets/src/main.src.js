const d3 = require('d3');
const cloud = require('d3-cloud');

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

    const drawWordCloud = (wordData) => {
        $(imageContainer).html('');

        const layout = cloud()
            .size([500, 500])
            .words(wordData.map((word) => {
                return {
                    text: word.text,
                    size: 10+Math.floor(word.value*1.5),
                    test: "haha"
                }
            }))
            .padding(5)
            .rotate(function() { return ~~(Math.random() * 2) * 90; })
            .font("Roboto")
            .fontSize(function(d) { return d.size; })
        .on("end", runD3);

        function runD3(){
            d3.select(imageContainer).append("svg")
                .attr("width", layout.size()[0])
                .attr("height", layout.size()[1])
              .append("g")
                .attr("transform", "translate(" + layout.size()[0] / 2 + "," + layout.size()[1] / 2 + ")")
              .selectAll("text")
                .data(wordData)
              .enter().append("text")
                .style("font-size", function(d) { return d.size + "px"; })
                .style("font-family", "Impact")
                .attr("text-anchor", "middle")
                .attr("transform", function(d) {
                  return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
                })
          .text(function(d) { return d.text; });

        layout.start();
    };

    
    }

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