<?php
/********************************************
** Functions JS de visualisation en direct **
** (c) 2018-2022 MeNearly@gmail.com GPL    **
** [except corrected mircToHtml]           **
*********************************************/
/**********
** V1.1b **
***********/

header("Content-type: text/javascript");
require_once("params.php");
?>
// tableau des channels à visualiser
var channels=<?=\bot\channels2JS()?>;

var interval=<?=\bot\refreshInterval?>; // 5 secondes dans l'idéal
var Interv=null; // handler du timer

// On commence au timestamp actuel - la durée d'historique
var firstTimestamp = Date.now()/1000-<?=\bot\messagesDisplay?>;
var timestamps=[];

// Currently running refresh
var running=0;

const zeroPad = (num, places) => String(num).padStart(places, '0');

//Remplacer tous les 'search' par 'replacement' dans une chaîne
String.prototype.replaceAll = function(search, replacement) {
    let target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};
// Initialize the timestamp for each viewed channel
function initTimestamps() {
  channels.forEach(function (name) {
    timestamps[name]=firstTimestamp;
  });
}

// Requête asynchrone
function createXHR() {
  let request = false;
  try {
    request = new XMLHttpRequest();
  } catch (e1) {
    try {
      request = new ActiveXObject('Microsoft.XMLHTTP');
    } catch (e2) {
      try {
        request = new ActiveXObject('Msxml2.XMLHTTP');
      } catch (e3) {
        request = false;
      }
    }
  }
  return request;
};

function startRefresh(interv) {
  let tmpInterv=parseInt(interv);
  if (!isNaN(tmpInterv)) {
    interval=tmpInterv;
    stopRefresh();
    refreshAll();
    if (interval>0)
      Interv=setInterval("refreshAll()",interval*1000);
  } else {
    alert("Bad refresh interval (must be seconds) :\n"+interv);
  }
};

function stopRefresh() {
  if (Interv)
    clearInterval(Interv);
};

function changeDate(event,channel) {
  event.preventDefault();
  let dateLbl=document.getElementById(channel+"_date_lbl");
  let dateValue=document.getElementById(channel+"_date");
  let refreshBtn=document.getElementById(channel+"RefreshBtn");
  let exportButton=document.getElementById(channel+"ExportBtn");
  if (dateValue.value=="") {
    refreshBtn.style.display="none";
    return;
  } else {
    refreshBtn.style.display="";
  }
  // TODAY
  let today=new Date();
  let dd0=zeroPad(today.getDate(),2);
  let mm0=zeroPad(today.getMonth()+1,2);
  let yy0=today.getFullYear();

  let str_today=yy0+"-"+mm0+"-"+dd0;

  // Chosen date

  let dateObj=new Date();
  let chosenDate=Date.parse(dateValue.value);
  dateObj.setTime(chosenDate);

  let dd=zeroPad(dateObj.getDate(),2);
  let mm=zeroPad(dateObj.getMonth()+1,2);
  let yy=dateObj.getFullYear();

  let str_chosen=yy+"-"+mm+"-"+dd;

  dateLbl.innerHTML=dateObj.toLocaleDateString('fr-FR',{ year: 'numeric', month: 'numeric', day: 'numeric' });

  // Show/Hide export button
  let isToday=false; /* for scrolling */
  if (str_chosen<str_today) {
    exportButton.style.display="";
  } else {
    exportButton.style.display="none";
    isToday=true;
  }

  let dateParam=yy+"_"+mm+"_"+dd;
  let channelTab=document.getElementById(channel+"Tab");
  channelTab.innerHTML="<i>Chargement...</i>";
  refreshView(channel,dateParam,isToday);
}

function exportDate(event,channel,date) {
  event.preventDefault();
  let chosenDate=new Date();
  chosenDate.setTime(Date.parse(date));
  let dd=zeroPad(chosenDate.getDate(),2);
  let mm=zeroPad(chosenDate.getMonth()+1,2);
  let yy=chosenDate.getFullYear();
  let dateParam=yy+"_"+mm+"_"+dd;
  channel=channel.replaceAll(/\+/,'@@@');
  channel=channel.replaceAll(/\#/,'%@@');
  window.open("export.php?date="+dateParam+"&channel="+channel);
}

function refreshAll(scroll=false) {
  channels.forEach(function (chan,index) {
    refresh(chan,scroll);
  });
}

function refresh(channel,scroll=false) {
  let xhr=createXHR();
  xhr.onreadystatechange = function() {
    if(this.readyState == 4) {
      let text=this.responseText;
      if(this.status != 200) {
        document.body.style.cursor='default';
        alert("Une erreur est survenue.");
        running--;
      } else {
        if (text!="") {
          refreshCallback(JSON.parse(text),channel,scroll);
        }
      }
    }
  };
  if (running<channels.length) {
    let timestamp=timestamps[channel];
    tmp_channel=channel.replaceAll(/\+/,'@@@');
    tmp_channel=channel.replaceAll(/\#/,'%@@');
    xhr.open("GET", `refresh.php?current=${timestamp}&channel=${tmp_channel}`);
    xhr.send(null);
    document.body.style.cursor='wait';
    running++;
  }
};

// Special for View because of the HUGE data !
function refreshView(channel,dateParam='',isToday) {
  let xhr=createXHR();
  xhr.onreadystatechange = function() {
    if(this.readyState == 4) {
      let text=this.responseText;
      document.body.style.cursor='default';
      if(this.status != 200) {
        alert("Une erreur est survenue.");
      } else {
        let channelTab=document.getElementById(channel+"Tab");
        if (text!="") {
          channelTab.innerHTML=text;
          channelTab.scrollTop=isToday?1000000:0; /* today? => end, else => from start */
          /* reverseVideo ... still complicated ^^ */
          let lines=channelTab.children;
          for (let i=0;i<lines.length;i++) {
            reverseVideo(lines[i]);
          }
        } else {
          let channelTab="<tr><td colspan='3' style='text-align:center;color:darkred'>Aucun message</td></tr>";
        }
      }
    }
  };
  tmp_channel=channel.replaceAll(/\+/,'@@@');
  tmp_channel=channel.replaceAll(/\#/,'%@@');
  xhr.open("GET", `refreshView.php?channel=${tmp_channel}&date=${dateParam}`);
  xhr.send(null);
  document.body.style.cursor='wait';
};

function refreshCallback(msgs,channel,scroll=false,init=false) {
  if (msgs == null || msgs.messages == null) return;
  // on actualise le current TS
  let l=msgs.messages.length;
  if (l>0) {
    timestamps[channel]=msgs.messages[l-1].timestamp;
  }
  let channelTab=document.getElementById(channel+"Tab");
  for (let i = 0; i < l; i++) {
    let m = msgs.messages[i];
    // On ajoute au tableau
      channelTab.innerHTML += "<tr><td class='tabline_date'>" + m.date + "</td><td class='tabline_nick' >" + mircToHtml(m.nick) + "</td><td class='tabline_msg'>" + mircToHtml(m.message) + "</td></tr>";
  }
//  if (scroll) {
    channelTab.scrollTop=1000000; /* today */
//  }

/* SPECIAL FOR REVERSE VIDEO ... complicated ^^ */
  let lines=channelTab.children;
  for (let i=0;i<lines.length;i++) {
    reverseVideo(lines[i]);
  }
  running--;
  if (running==0)
    document.body.style.cursor='default';

}

function openChannelTab(evt, channel) {
  // Declare all variables
  var i, tabcontent, tablinks;

  // Get all elements with class="tabcontent" and hide them
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }

  // Get all elements with class="tablinks" and remove the class "active"
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }

  // Show the current tab, and add an "active" class to the button that opened the tab
  document.getElementById(channel).style.display = "block";
  evt.currentTarget.className += " active";
}

function mircToHtml(text) {
  /* control codes */
  /* Corrections/améliorations by MeNearly@gmail.com */

  if (!text) return "<span style='color:red'>ligne en erreur</span>";
  let rex = /[\x03](\d{0,2})(,\d{1,2})?([^\x03\x0F]*)(?:[\x03](?!\d))?/, matches, colors;
  if (rex.test(text)) {
    while (cp = rex.exec(text)) {
      let bgok = '';
      if (cp[2]) {
        let cbg = cp[2];
        if (cbg)
          bgok+=' bg'+cbg.substring(1,cbg.length);
      }
      text = text.replace(cp[0], '<span class="fg' + cp[1] + bgok + '">' + cp[3] + '</span>');
    }
  }
  /* bold, italics, underline, reverse, strikethrough */
  let buirs = [
    [/\x02([^\x02]+)(\x02)?/, ["<b>", "</b>"]],
    [/\x1F([^\x1F]+)(\x1F)?/, ["<u>", "</u>"]],
    [/\x1D([^\x1D]+)(\x1D)?/, ["<i>", "</i>"]],

  /* strikethrough */
    [/\x1E([^\x1E]+)(\x1E)?/, ["<span style='text-decoration:line-through'>", "</span>"]],

  /* Reverse Video, virtual class, see function reverseVideo */
    [/\x16([^\x16]+)(\x16)?/, ["<span class='Xreverse'>", "</span>"]]
  ];
  for (let i = 0; i < buirs.length; i++) {
    let bc = buirs[i][0];
    let style = buirs[i][1];
    if (bc.test(text)) {
      while (bmatch = bc.exec(text)) {
        text = text.replace(bmatch[0], style[0] + bmatch[1] + style[1]);
      }
    }
  }
  text = text.replace(/(https?:\/\/[^ ]+)/g, (match, link) => {
  // remove ending slash if there is one
    link = link.replace(/\/?$/, '');
    return `<a href="${link}" target="_blank">${link}</a>`;
  });
  text = text.replace(/(x0F)/g, (match, whole) => { return "";});

  return text;
}

function reverseVideo(line) {
  let col,bcol,elem=null,orig=null;
  let childs=line.childNodes,cstyle;
  let tmp;
  for (let i=0;i<childs.length;i++) {
    elem=childs[i];
    orig=elem;
    if (elem.classList.contains("Xreverse")) {
      cstyle=getComputedStyle(elem);
      while (elem && cstyle.color=="") {
        cstyle=getComputedStyle(elem)
        elem=elem.parentElement;
      }
      if (cstyle && cstyle.color!="") {
        col=cstyle.color;
        /* search for a bgcolor */
        elem=orig;
        while (elem && (cstyle.backgroundColor=="" || cstyle.backgroundColor=="rgba(0, 0, 0, 0)")) {
          cstyle=getComputedStyle(elem)
          elem=elem.parentElement;
        }
        bcol=cstyle.backgroundColor;
        if (bcol.endsWith("0)") && bcol.startsWith("rgba"))
          bcol=bcol.substring(0,bcol.lastIndexOf(","))+",255)";
        orig.style.color=bcol;
        orig.style.backgroundColor=col;
        orig.className="";
      }
    }
  }
};

initTimestamps();
