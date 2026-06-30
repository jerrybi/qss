import {EMOTIONS_ZHCN,invertKeyValues} from '../views/DataSource';
function Emoj() {

    let emojiReg = new RegExp('\\[[^\\]]+\\]','g'); //表情符号正则表达式
    let tempSendTxtArray = [];
    let emojSize = 16;
    // public method for encoding
    this.measure = function (params) {
        this.tempSendTxtArray = [];
        let content = params.content;
        let maxWidth = params.maxWidth;
        let fontSize = params.fontSize?(params.fontSize):(15);
        let height = 0;
        _matchContentString(input);
        for(let i=0;i<this.tempSendTxtArray.length;i++){
            let item = this.tempSendTxtArray[i];
            if(item.isEmoj){

            }else {

            }
        }
        return output;
    }

    _matchContentString = function(textContent){
        // 匹配得到index并放入数组中
        let currentTextLength = textContent.length;
        let emojiIndex = textContent.search(emojiReg);
        let checkIndexArray = [];
        // 若匹配不到，则直接返回一个全文本
        if (emojiIndex === -1) {
            this.tempSendTxtArray.push({isEmoj:false,content:textContent.substring(0,currentTextLength)});
        } else {
            if (emojiIndex !== -1) {
                checkIndexArray.push(emojiIndex);
            }
            // 取index最小者
            let minIndex = Math.min(...checkIndexArray);
            // 将0-index部分返回文本
            this.tempSendTxtArray.push({isEmoj:false,content:textContent.substring(0, minIndex)});
            // 将index部分作分别处理
            this._matchEmojiString(textContent.substring(minIndex));
        }
    }

    _matchEmojiString = function(emojiStr) {
        let castStr = emojiStr.match(emojiReg);
        let emojiLength = castStr[0].length;
        let emotoins_code = invertKeyValues(EMOTIONS_ZHCN);
        this.tempSendTxtArray.push({isEmoj:true,content:emotoins_code[castStr[0]]});
        this._matchContentString(emojiStr.substring(emojiLength));
    }
}

let base = new Emoj();

module.exports = {
    measure: base.measure
};
