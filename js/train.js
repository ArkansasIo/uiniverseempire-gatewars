var queryString;

function trainthis(page,type,id){
    if (type == "post") { setQueryStringTrain(page); }
	var url = "modules/"+page+".php?id="+id+"&time=1";//+date.getTime();
    trainReq("POST",url,true);
}

function trainReq(reqType,url,asynch){
    var train;
    if(window.XMLHttpRequest){
        train = new XMLHttpRequest();
    } else if (window.ActiveXObject){
        try {
            train = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                train = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e2) {
                train = null;
            }
        }
    }
    if(!train){
        alert("Your browser does not permit the use of all "+
        "of this application's features!");
        return;
    }

    train.onreadystatechange = function(){
        if(train.readyState == 4){
            if(train.status == 200){
                var target = document.getElementById("display");
                if(target){
                    stylizeDiv(train.responseText, target);
                }
            }
            // status 0 / errors handled silently - training is non-critical
        }
    };

    train.open(reqType,url,asynch);
    if(reqType.toUpperCase() == "POST"){
        train.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    }
    train.send(reqType.toUpperCase() == "POST" ? queryString : null);
}

function setQueryStringTrain(page){
    queryString="";
	var frm = document.getElementById(page);
    if(!frm || !frm.elements){
        return;
    }
    var parts = [];
    var numberElements = frm.elements.length;
    for(var i = 0; i < numberElements; i++)  {
            var el = frm.elements[i];
            if(el && el.name){
                parts.push(el.name+"="+encodeURIComponent(el.value));
            }
    }
    queryString = parts.join("&");
	
}