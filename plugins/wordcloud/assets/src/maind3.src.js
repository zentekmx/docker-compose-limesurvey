import { saveAs } from 'file-saver';
import {getSVGString, svgString2Image} from './processSVG.js';

const WordCloudProcessor = function(options){
    const questionSelector = '#WordCloud--QuestionSelector';
    const loadingBlock = '#WordCloud--loadingBlock';
    const imageContainer = '#WordCloud--imagecontainer';
    const downloadButtonSelector = '#WordCloud--Action--DownloadPNG';
    const colorPickerClass = '.WordCloud--Action--ColorPicker'
    const colorPickerStartColorSelector = '#WordCloud--ColorPicker-startColor'
    const colorPickerFinalColorSelector = '#WordCloud--ColorPicker-finalColor'

    //OPTIONS
    const cloudWidth = options.cloudWidth || 800;
    const cloudHeight = options.cloudHeight || 500;
    const fontPadding = options.fontPadding || 5;
    const wordAngle = options.wordAngle || 45;
    const minFontSize = options.minFontSize || 10;

    let currentWordList = null;

    const toggleLoader = (isLoading) => {
        if(isLoading) {
            $(loadingBlock).css('display','');
        } else {
            $(loadingBlock).css('display','none');
        }
    };

    const getStartColor = function() {
        return $(colorPickerStartColorSelector).val();
    }
    const getFinalColor = function() {
        return $(colorPickerFinalColorSelector).val();
    }

    const setCurrentWordList = (wordListObject) => {
        currentWordList = LS.ld.toPairs(wordListObject);
    }

    const drawWordCloud = (wordListObject = null) => {
        $(imageContainer).html('');
        
        const color = d3.scaleLinear()
            .domain([0,100].reverse())
            .range([getStartColor(),getFinalColor()]);

        toggleLoader(true);
        d3.layout.cloud().size([cloudWidth, cloudHeight])
            .words(currentWordList.map((word) => {
                return {
                    text: word[0],
                    size: (word[1]<minFontSize ? minFontSize : word[1])*3
                }
            }))
            .padding(fontPadding)
            .rotate(function() { 
                let randomOrientation = Math.floor(Math.random()*3); 
                return randomOrientation==0 ? -wordAngle : (randomOrientation==1 ? 0 : wordAngle); 
            })
            .fontSize(function(d) { return d.size; })
            .on("end", draw)
            .start();

        function draw(words) {
            d3.select(imageContainer).append("svg")
                .attr("width", cloudWidth)
                .attr("height", cloudHeight)
                .attr("class", "wordcloud")
                .append("g")
                // without the transform, words words would get cutoff to the left and top, they would
                // appear outside of the SVG area
                .attr("transform", "translate(" + cloudWidth / 2 + "," + cloudHeight / 2 + ")")
                .selectAll("text")
                .data(words)
                .enter().append("text")
                .style("font-size", function(d) { return d.size + "px"; })
                .style("font-family", "Roboto")
                .attr("text-anchor", "middle")
                .style("fill", function(d, i) { return color(i); })
                .attr("transform", function(d) {
                    return "translate(" + [d.x, d.y] + ")rotate(" + d.rotate + ")";
                })
                .text(function(d) { return d.text; });
                toggleLoader(false);
        }
    };

    const reloadQuestionData = () => {
        let currentQid = $(questionSelector).val();
        toggleLoader(true);
        $.ajax({
            url: options.getQuestionDataUrl,
            data: {qid: currentQid},
            success: (wordListObject) => {
                setCurrentWordList(wordListObject);
                drawWordCloud();
            },
            error: (err,xhr) => {console.error("WordCloudError => ", err); toggleLoader(false);}
        });
    }

    const triggerDownload = () => {
        let currentQid = $(questionSelector).val();
        var svgString = getSVGString($(imageContainer).find('svg').first()[0]);
        svgString2Image( svgString, 2*cloudWidth, 2*cloudHeight, 'png', (dataBlob, fileSize) => {
            saveAs( dataBlob, 'WordCloud-Question-'+currentQid );
        } ); // passes Blob and filesize String to the callback
    };

    const updateColors = () => {};

    const bind = () => {
        $(questionSelector).on('change', reloadQuestionData)
        $(downloadButtonSelector).on('click', triggerDownload)
        $(colorPickerClass).on('change', function(){drawWordCloud();});
        toggleLoader(false);
    }

    return {
        bind,
        reloadQuestionData,
    };
};

window.LS.getWordCloudProcessorFactory = function(){
    return WordCloudProcessor;
};