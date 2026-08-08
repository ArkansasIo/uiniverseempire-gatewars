/*
 * MIT License
 *
 * Copyright (c) 2026 Universe Civilization : Empire at wars
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

var request;
var queryString;   //will hold the POSTed data
var a;
function getStoredTheme() {
    try {
        var storedTheme = window.localStorage.getItem('sgwTheme');
        if (storedTheme === 'white' || storedTheme === 'og' || storedTheme === 'blue' || storedTheme === 'stargate') {
            return storedTheme;
        }
    } catch (e) {
        // Ignore storage errors and fall back to Blue.
    }
    return 'blue';
}

function setTheme(themeName) {
    var normalizedTheme = (themeName || getStoredTheme() || 'blue').toLowerCase();
    if (normalizedTheme !== 'white' && normalizedTheme !== 'og' && normalizedTheme !== 'blue' && normalizedTheme !== 'stargate') {
        normalizedTheme = 'blue';
    }

    if (document.body) {
        document.body.classList.remove('theme-white', 'theme-og', 'theme-blue', 'theme-stargate');
        document.body.classList.add('theme-' + normalizedTheme);
    }

    try {
        window.localStorage.setItem('sgwTheme', normalizedTheme);
    } catch (e) {
        // Ignore storage errors.
    }

    var buttons = document.querySelectorAll('.theme-option');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.toggle('active', buttons[i].getAttribute('data-theme') === normalizedTheme);
    }

    return normalizedTheme;
}

function initThemePicker() {
    setTheme(getStoredTheme());
}

function autocomplete(sender,ev) {
if (( ev.keyCode >= 48 && ev.keyCode <= 57 )
  ||  ( ev.keyCode >= 65 && ev.keyCode <= 90 )) {
	var sent = sender.value;
    // Prepare a server request:
    var httpreq = createXhr();
    if (!httpreq) { return; }
    var url = "userlist.php?val="+encodeURIComponent(sent);
    httpreq.open("GET", url, true);

    var original_text = sender.value;

    // Response function:
    httpreq.onreadystatechange = function () {
      if (httpreq.readyState == 4 && httpreq.status == 200) {
		var obj;
		try {
			obj = JSON.parse(httpreq.responseText);
		} catch (e) {
			obj = null;
		}
		if (!obj || !obj.result || !obj.result.length) { return; }
        var suggestion = obj.result[0][0];
		var userID = document.getElementById ('userID2');
		if (userID) { userID.value = obj.result[0][3]; }
        var toUser = document.getElementById ('toUser1');         

        if (suggestion && toUser && toUser.value == original_text) {
			toUser.value = suggestion;
			if (toUser.setSelectionRange) {
				toUser.selectionStart = original_text.length;
				toUser.selectionEnd   = suggestion.length;
			}
        }
      }
    }
    httpreq.send (null);
	}
}
function toggle_visible (elName) {

    var el = document.getElementById (elName);
    var isVisible = (el.style.visibility == "hidden") ? true : false;

    el.style.visibility = isVisible ? "visible" : "hidden";
    el.style.display = isVisible ? "inline" : "none";
}
function sendData(page,type,id,atype,subject,message){
    if (typeof bb_save_state === 'function') {
        try {
            bb_save_state();
        } catch (e) {
            // Continue even if back-button cache state is unavailable.
        }
    }
    if (typeof id === "undefined" || id === null || id === "") {
        id = "mainDisplay";
    }
    if (typeof atype === "undefined" || atype === null) {
        atype = "";
    }
	date = new Date();
	var url = "modules/"+page+".php?id="+id+"&time="+date.getTime()+"&atype="+atype;
	if (typeof subject !== "undefined" && subject !== null && subject !== "") {
		url += "&subject="+encodeURIComponent(subject);
	}
	if (typeof message !== "undefined" && message !== null && message !== "") {
		url += "&message="+encodeURIComponent(message);
	}
	if (type =="post")
	{
	setQueryString();
    httpRequest("POST",url,true);
	}else{
    httpRequest("GET",url,true);
	}
}

function mainUpdate(page,text)
{
	date = new Date();
    var url = "indexpages/"+page+".php?time="+date.getTime();
    httpRequest("GET",url,true);
	a = text;
}

function rollUpDate(text)
{
	stylizeDiv(text,document.getElementById("rollover"));
}

function autoclear()
{
	stylizeDiv(a,document.getElementById("rollover"));
}

//shared factory for the XMLHttpRequest object
function createXhr() {
    if (window.XMLHttpRequest) {
        return new XMLHttpRequest();
    }
    if (window.ActiveXObject) {
        try {
            return new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                return new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e2) {
                return null;
            }
        }
    }
    return null;
}

function httpRequest(reqType,url,asynch){
    var request = createXhr();
    //the request could still be null if neither ActiveXObject
    //initializations succeeded
    if(!request){
        alert("Your browser does not permit the use of all "+
        "of this application's features!");
        return;
    }

    request.onreadystatechange=function(){
        if(request.readyState != 4){
            return;
        }
        if(request.status == 200){
            var doc = request.responseText || "";
            // A full HTML document means the server redirected us to the
            // landing page (e.g. the session expired). Navigate the shell
            // there instead of injecting it as a fragment.
            if(/^\s*<!DOCTYPE html/i.test(doc) || /^\s*<html/i.test(doc)){
                window.location.href = "index.php";
                return;
            }
            var target = document.getElementById("mainDisplay");
            if(target){
                stylizeDiv(doc,target);
            }
            queryString="";
        } else if(request.status > 0){
            alert("A problem occurred communicating with the server program (HTTP "+
                request.status+"). Please try again.");
        }
        // status 0: request aborted or offline - handle silently
    };

    request.open(reqType,url,asynch);
    if(reqType.toUpperCase() == "POST"){
        request.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    }
    request.send(reqType.toUpperCase() == "POST" ? queryString : null);
    queryString=null;
}

function stylizeDiv(bdyTxt,div){
    //reset DIV content
    div.innerHTML="";
    div.innerHTML = bdyTxt;

    // Execute inline and external scripts from dynamically injected module HTML.
    var scripts = div.querySelectorAll('script');
    for (var i = 0; i < scripts.length; i++) {
        var oldScript = scripts[i];
        var newScript = document.createElement('script');
        if (oldScript.src) {
            newScript.src = oldScript.src;
        }
        if (oldScript.type) {
            newScript.type = oldScript.type;
        }
        newScript.text = oldScript.text || oldScript.textContent || oldScript.innerHTML || '';
        oldScript.parentNode.replaceChild(newScript, oldScript);
    }
}

function setQueryString(){
    queryString="";
    var frm = document.forms[1];
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

function MM_swapImgRestore() { //v3.0
  var i,x,a=document.MM_sr; for(i=0;a&&i<a.length&&(x=a[i])&&x.oSrc;i++) x.src=x.oSrc;
}

function MM_preloadImages() { //v3.0
  var d=document; if(d.images){ if(!d.MM_p) d.MM_p=new Array();
    var i,j=d.MM_p.length,a=MM_preloadImages.arguments; for(i=0; i<a.length; i++)
    if (a[i].indexOf("#")!=0){ d.MM_p[j]=new Image; d.MM_p[j++].src=a[i];}}
}

function MM_findObj(n, d) { //v4.01
  var p,i,x;  if(!d) d=document; if((p=n.indexOf("?"))>0&&parent.frames.length) {
    d=parent.frames[n.substring(p+1)].document; n=n.substring(0,p);}
  if(!(x=d[n])&&d.all) x=d.all[n]; for (i=0;!x&&i<d.forms.length;i++) x=d.forms[i][n];
  for(i=0;!x&&d.layers&&i<d.layers.length;i++) x=MM_findObj(n,d.layers[i].document);
  if(!x && d.getElementById) x=d.getElementById(n); return x;
}

function MM_swapImage() { //v3.0
  var i,j=0,x,a=MM_swapImage.arguments; document.MM_sr=new Array; for(i=0;i<(a.length-2);i+=3)
   if ((x=MM_findObj(a[i]))!=null){document.MM_sr[j++]=x; if(!x.oSrc) x.oSrc=x.src; x.src=a[i+2];}
}

function disableFormElements(formD)
{
    formD = document.getElementById(formD);
    for (var i=0;i<formD.elements.length;i++)
   {
      var e = document.formD.elements[i];
      e.disabled=true;
   }
}

function disableFormElementsAfterSubmit(in_Name)
{
   setTimeout("disableFormElements(" + in_Name + ")", 10);
}
