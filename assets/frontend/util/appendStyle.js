window.appendStyle = function (code, o){
    var css = document.createElement('style');
    css.type = 'text/css';
    //css.media = 'screen';
    rule = document.createTextNode(code);
    if (css.styleSheet) css.styleSheet.cssText = rule.nodeValue;
    else css.appendChild(rule);
    document.getElementsByTagName('head')[0].appendChild(css);
};
