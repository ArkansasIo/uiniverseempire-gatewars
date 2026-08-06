/*
 * MIT License
 *
 * Copyright (c) 2026 Stargate Wars contributors
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

var autoString;   //will hold the POSTed data

function autoLoad(){
    var url="stats.php";
    autoRequest("GET",url,true);
    setTimeout(autoLoad,15000);
}

function autoRequest(reqType,url,asynch){
    var auto;
    if(window.XMLHttpRequest){
        auto = new XMLHttpRequest();
    } else if (window.ActiveXObject){
        try {
            auto = new ActiveXObject("Msxml2.XMLHTTP");
        } catch (e) {
            try {
                auto = new ActiveXObject("Microsoft.XMLHTTP");
            } catch (e2) {
                auto = null;
            }
        }
    }
    //the auto could still be null if neither ActiveXObject
    //initializations succeeded
    if(!auto){
        return;
    }

    auto.onreadystatechange = function(){
        if(auto.readyState != 4){
            return;
        }
        if(auto.status != 200){
            // session expiry / transient error - handled silently
            return;
        }
        var obj;
        try {
            obj = eval("(" + auto.responseText + ")");
        } catch (e) {
            return;
        }
        if(!obj || Object.prototype.toString.call(obj) !== "[object Array]"){
            return;
        }
        stylizeDiv(obj[6],document.getElementById("next"));
        stylizeDiv(obj[5],document.getElementById("messages"));
        stylizeDiv(obj[4],document.getElementById("time"));
        if (document.getElementById("serverTime")) { stylizeDiv(obj[4],document.getElementById("serverTime")); }
        stylizeDiv(obj[3],document.getElementById("turns"));
        stylizeDiv(obj[2],document.getElementById("isRank"));
        stylizeDiv(obj[1],document.getElementById("inBank"));
        stylizeDiv(obj[0],document.getElementById("inHand"));

        if (document.getElementById("metal")) { stylizeDiv(obj[7],document.getElementById("metal")); }
        if (document.getElementById("crystal")) { stylizeDiv(obj[8],document.getElementById("crystal")); }
        if (document.getElementById("deuterium")) { stylizeDiv(obj[9],document.getElementById("deuterium")); }
        if (document.getElementById("food")) { stylizeDiv(obj[10],document.getElementById("food")); }
        if (document.getElementById("water")) { stylizeDiv(obj[11],document.getElementById("water")); }
        if (document.getElementById("population")) { stylizeDiv(obj[12],document.getElementById("population")); }
        if (document.getElementById("energy")) { stylizeDiv(obj[13],document.getElementById("energy")); }
    };

    auto.open(reqType,url,asynch);
    if(reqType.toUpperCase() == "POST"){
        auto.setRequestHeader("Content-Type",
            "application/x-www-form-urlencoded; charset=UTF-8");
    }
    auto.send(reqType.toUpperCase() == "POST" ? autoString : null);
}

