/**
 * @file
 * Script to run Google translate service
 */

'use strict';

jQuery(document).ready( function () {

  translate.init();

});

let translate = function(d){
  return {
    name:"translate", settings: {
      container: ".translate_container", enableEventTracking: true, analyticsNs: "Translate", analyticsAction: "Translate"
    }
    , init:function() {
      this.writeLanguageList();
      this.bindDomEvents()
    }
    , exec:function() {
      jQuery(this.settings.container).html(this.settings.template);
      this.writeLanguageList();
      this.bindDomEvents()
    }
    , bindDomEvents:function() {
      let e=this;
      jQuery("#cob_translate, #langCloseButton").on("click", function(f) {
          f.preventDefault();
          e.toggleLanguages();
          jQuery('#overlay-background').addClass('md');
        }
      );
      jQuery("#viewDisclaimer, #closeDisclaimer").on("click", function(f) {
          f.preventDefault();
          e.showDisclaimer()
        }
      );
      jQuery("#disclaimerLanguageSelect").on("change", function(f) {
          e.selectDisclaimerLanguage()
        }
      );
      jQuery(".translateLink").on("click", function(g) {
          let f=jQuery(this);
          e.trackEvent(document.location.href, function() {
              document.location=f.attr("href")
            }
          )
        }
      )
    }
    , writeDisclaimer:function() {
      let g="";
      g+='<form id="translateForm" class="translate-form"><label for="disclaimerLanguageSelect">View Disclaimer in: </label><select id="disclaimerLanguageSelect" name="disclaimerLanguageSelect" class="translate-select-disclaimer">';
      g+='<option value="English">English</option>';
      g+='<option value="Spanish">Spanish</option>';
      g+='<option value="ChineseS">Chinese - Simplified</option>';
      g+='<option value="ChineseT">Chinese - Traditional</option></select>';
      let h='<div id="English" class="translate-disclaimer disclaimer"><h4>About Translations on Boston.gov</h4>';
      h+="<p>The City of Boston Department of Innovation and Technology (“DoIT”) offers translations of the content on Boston.gov through the Google Translate web translator (translate.google.com). Because Google Translate is an external website, DoIT does not control the quality or accuracy of translated content. This may result in inaccurate translated text, or other errors in images and the general appearance of translated pages.</p>";
      h+="<p>DoIT uses Google Translate to provide language translations of its content. Google Translate is a free, automated service that relies on data and technology to provide its translations. The Google Translate feature is provided for informational purposes only. Translations cannot be guaranteed as exact or without the inclusion of incorrect or inappropriate language. Google Translate is a third-party service and site users will be leaving DoIT to utilize translated content. As such, DoIT does not guarantee and does not accept responsibility for, the accuracy, reliability, or performance of this service nor the limitations provided by this service, such as the inability to translate specific files like PDFs and graphics (e.g. .jpgs, .gifs, etc.).</p>";
      h+="<p>However, you can report incorrect or substandard translations and contribute better translations using Google Translate.</p>";
      h+="<ol>";
      h+="<li>First, hover over and click on any text containing an error. A pop up box should appear.</li>";
      h+="<li>Next, click “Contribute a better translation”.</li>";
      h+="<li>Double click the area of the pop up that reads “Click a word for alternative translations, or double-click to edit directly.”</li>";
      h+="<li>Make your edits directly to the text in the text box.</li>";
      h+="<li>Finally, press Contribute to contribute your suggested edits.</li>";
      h+="</ol>";
      h+="<p>More information about contributing to Google Translate can be found <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>here.</a></p>";
      h+="<p>Please note that DoIT does not control the process by which contributed translations are incorporated into the Google web translator.</p>";
      h+="<p>The City of Boston is committed to improving the quality and breadth of multilingual content on our website. Critical information regarding Boston’s response to the coronavirus emergency is already available in multiple languages and can be found here:</p>";
      h+="<p>Spanish: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      h+="<p>Haitian Creole: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      h+="<p>Cape Verdean: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      h+="<p>Portuguese: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      h+="<p>French: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      h+="<p>Chinese: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      h+="<p>Vietnamese: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      h+="<p>Russian: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      h+="<p>Somali: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      h+="<p>Arabic: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let j='<div id="Spanish" class="translate-disclaimer disclaimer" style="display:none;"><h4>Acerca de las traducciones en Boston.gov</h4>';
      j+="<p>El Departamento de Innovación y Tecnología de la Ciudad de Boston (”DoIT”) ofrece traducciones del contenido en Boston.gov a través del traductor web Google Translate (translate.google.com). Porque Google Translate es un sitio web externo , DoIT no controla la calidad ni la precisión del contenido traducido. Esto puede resultar en texto traducido incorrecto u otros errores en las imágenes y la apariencia general de las páginas traducidas. </p> ";
      j+="<p>DoIT utiliza Google Translate para proporcionar traducciones de su contenido al idioma. Google Translate es un servicio gratuito y automatizado que se basa en datos y tecnología para proporcionar sus traducciones. La función Google Translate se proporciona únicamente con fines informativos. Las traducciones no pueden estar garantizado como exacto o sin la inclusión de un lenguaje incorrecto o inapropiado. Google Translate es un servicio de terceros y los usuarios del sitio dejarán DoIT para utilizar el contenido traducido. Como tal, DoIT no garantiza y no acepta responsabilidad por la precisión , la fiabilidad o el rendimiento de este servicio ni las limitaciones proporcionadas por este servicio, como la imposibilidad de traducir archivos específicos como PDF y gráficos (por ejemplo, .jpgs, .gifs, etc.). </p> ";
      j+="<p>Sin embargo, puede informar traducciones incorrectas o de calidad inferior y contribuir con mejores traducciones usando Google Translate. </p>";
      j+="<ol>";
      j+="<li>Primero, desplace el mouse y haga clic en cualquier texto que contenga un error. Debería aparecer un cuadro emergente. </li>";
      j+="<li>A continuación, haga clic en ”Contribuir a una mejor traducción”. </li>";
      j+="<li>Haga doble clic en el área de la ventana emergente que dic ”Haga clic en una palabra para traducciones alternativas, o haga doble clic para editar directamente.” </li>";
      j+="<li>Realice sus ediciones directamente al texto en el cuadro de texto. </li>";
      j+="<li>Finalmente, presione Contribuir para contribuir con las ediciones sugeridas. </li>";
      j+="</ol>";
      j+="<p>Puede encontrar más información sobre cómo contribuir al Traductor de Google <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'> aquí. </a> </p> ";
      j+="<p>Tenga en cuenta que DoIT no controla el proceso mediante el cual las traducciones contribuidas se incorporan al traductor web de Google. </p>";
      j+="<p>La Ciudad de Boston se compromete a mejorar la calidad y la amplitud del contenido multilingüe en nuestro sitio web. La información crítica sobre la respuesta de Boston a la emergencia del coronavirus ya está disponible en varios idiomas y se puede encontrar aquí: </p> ";
      j+="<p>español: <a href='https://www.boston.gov/covid-19-es'> boston.gov/covid19-es</a></p>";
      j+="<p>criollo haitiano: <a href='https://www.boston.gov/covid-19-hc'> boston.gov/covid19-hc</a></p>";
      j+="<p>caboverdiano: <a href='https://www.boston.gov/covid-19-cv'> boston.gov/covid19-cv</a></p>";
      j+="<p>portugués: <a href='https://www.boston.gov/covid-19-pt'> boston.gov/covid19-pt</a></p>";
      j+="<p>francés: <a href='https://www.boston.gov/covid-19-fr'> boston.gov/covid19-fr</a></p>";
      j+="<p>chino: <a href='https://www.boston.gov/covid-19-zh'> boston.gov/covid19-zh</a></p>";
      j+="<p>vietnamita: <a href='https://www.boston.gov/covid-19-vi'> boston.gov/covid19-vi</a></p>";
      j+="<p>ruso: <a href='https://www.boston.gov/covid-19-ru'> boston.gov/covid19-ru</a></p>";
      j+="<p>Somalí: <a href='https://www.boston.gov/covid-19-so'> boston.gov/covid19-so</a></p>";
      j+="<p>árabe: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div> ";
      let e='<div id="ChineseS" class="translate-disclaimer disclaimer" style="display:none;"><h4>Google翻译免责声明</h4>';
      e+="<p>波士顿信息技术部（“DoIT”）通过Google翻译提供内容的翻译。由于Google翻译是外部网站，因此DoIT不能控制翻译内容的质量或准确性。所有DoIT内容均通过Google翻译过滤，这可能会导致文本，图像部分以及翻译页面的整体外观出现意外和不可预测的下降。 Google翻译可能会维护独特的隐私权和使用政策。这些政策不受DoIT的控制，并且与DoIT的隐私和使用政策无关。选择翻译选项后，将通知用户他们即将离开DoIT网站。如果对翻译后的内容有任何疑问，用户应查阅DoIT网站上的原始英语内容。</p>";
      e+="<p>DoIT使用Google翻译为其网站内容提供语言翻译服务。Google翻译是一项免费的自动服务，其依靠相关数据和技术来提供翻译服务。提供Google翻译服务的目的仅为提供相关信息，因此DoIT无法保证翻译后的内容与原文完全相同或不包含任何不正确或不适宜的语言。Google翻译是一项第三方服务，而DoIT网站使用者将离开DoIT网站以便查阅翻译后的内容。因此，DoIT并不保证这项服务的准确性、可靠性、质量和局限性（如这项服务无法翻译PDF、图形（如jpgs、gifs等）格式的文件），也不对此负责。</p>";
      e+="<p>Google翻译是DoIT为其网站使用者提供的一种网上工具。尽管如此，DoIT并不直接为该网站服务提供担保，也不表明使用者只能使用Google翻译提供的服务。所有DoIT网站访问者可以选择使用其它工具以满足其翻译需要。任何使用翻译后（无论是通过Google翻译还是通过其它翻译服务）的DoIT网站内容的个人或机构应自行承担风险。DoIT不对因使用或依赖翻译后的内容所造成的损失、损害或问题负责。DoIT不对任何网站访问者与使用Google翻译功能或内容相关的活动负责。</p>";
      e+="<p>Google翻译服务是DoIT为其网站使用者提供的一种翻译工具，其唯一的目的是为英语非母语的网站使用者提供方便。Google直接提供动态的内容翻译服务，而DoIT不直接控制翻译后的内容，即使其使用该工具。因此，在各种情况下，使用者应以DoIT为其直接提供的英文内容为准。</p></div>";
      let f='<div id="ChineseT" class="translate-disclaimer disclaimer" style="display:none;"><h4>Google翻譯免責聲明</h4>';
      f+="<p>波士頓信息技術部（“DoIT”）通過Google翻譯提供內容的翻譯。由於Google翻譯是外部網站，因此DoIT不能控制翻譯內容的質量或準確性。所有DoIT內容均通過Google翻譯過濾，這可能會導致文本，圖像部分以及翻譯頁面的整體外觀出現意外和不可預測的下降。 Google翻譯可能會維護獨特的隱私權和使用政策。這些政策不受DoIT的控制，並且與DoIT的隱私和使用政策無關。選擇翻譯選項後，將通知用戶他們即將離開DoIT網站。如果對翻譯後的內容有任何疑問，用戶應查閱DoIT網站上的原始英語內容。</p>";
      f+="<p>DoIT使用Google翻譯為其網站內容提供語言翻譯服務。 Google翻譯依靠相關數據和技術提供免費的自動化翻譯服務。提供Google翻譯服務的目的僅為提供相關信息，因此DoIT無法保證翻譯後的內容與原文完全相同或不包含任何不正確或不適宜的語言。 Google翻譯是一項第三方服務，且DoIT網站使用者將離開DoIT網站才能查閱翻譯後的內容。因此，DoIT並不保證這項服務的準確性、可靠性、質量或局限性（比如，這項服務無法翻譯PDF、圖形（如jpgs、gifs等）等格式的文件），也不對此負責。</p>";
      f+="<p>Google翻譯是DoIT為其網站使用者提供的一項網上工具。儘管如此，DoIT並不直接為該網站服務提供擔保，也不表明使用者只能使用Google翻譯提供的服務。所有DoIT網站訪問者可以選擇使用其它工具以滿足其翻譯需要。任何使用翻譯後（無論是通過Google翻譯還是通過其它翻譯服務）的DoIT網站內容的個人或機構應自行承擔風險。 DoIT不對因使用或依賴翻譯後的內容所造成的損失、損害或問題負責。 DoIT不對任何網站訪問者與使用Google翻譯功能或內容相關的活動負責。</p>";
      f+="<p>Google翻譯服務是DoIT為其網站使用者提供的一項翻譯工具，其唯一的目的是為英語非母語的網站使用者提供方便。 Google直接提供動態的內容翻譯服務，而DoIT不直接控制翻譯後的內容，即使其使用該工具。因此，在各種情況下，使用者應以DoIT為其直接提供的英文內容為準。</p></div>";
      g+=h;
      g+=j;
      g+=e;
      g+=f;
      g+="</form>";
      return g
    }
    , writeLanguageList:function() {
      let l=window.location.toString();
      if(l.slice(-1)=="#") {
        let k=l.substring(1, (l.length-1));
        l=k
      }
      let h="http://translate.google.com/translate?hl=en&sl=en&u="+l+"&tl=";
      let j="";
      j+='<div id="languages" class="translate-languages"><a class="md-cb" href="#" id="langCloseButton"></a>';
      let g=[["af", "Afrikaans"], ["sq", "Albanian"], ["am", "Amharic"], ["ar", "Arabic"], ["hy", "Armenian"], ["az", "Azerbaijani"], ["eu", "Basque"], ["be", "Belarusian"], ["bn", "Bengali"], ["bs", "Bosnian"], ["bg", "Bulgarian"], ["ca", "Catalan"], ["ceb", "Cebuano"], ["ny", "Chichewa"], ["zh-CN", "Chinese (Simplified)"], ["zh-TW", "Chinese (Traditional)"], ["co", "Corsican"], ["hr", "Croatian"], ["cs", "Czech"], ["da", "Danish"], ["nl", "Dutch"], ["eo", "Esperanto"], ["et", "Estonian"], ["tl", "Filipino"], ["fi", "Finnish"], ["fr", "French"], ["fy", "Frisian"], ["gl", "Galician"], ["ka", "Georgian"], ["de", "German"], ["el", "Greek"], ["gu", "Gujarati"], ["ht", "Haitian Creole"], ["ha", "Hausa"], ["haw", "Hawaiian"], ["iw", "Hebrew"], ["hi", "Hindi"], ["hmn", "Hmong"], ["hu", "Hungarian"], ["is", "Icelandic"], ["ig", "Igbo"], ["id", "Indonesian"], ["ga", "Irish"], ["it", "Italian"], ["ja", "Japanese"], ["jw", "Javanese"], ["kn", "Kannada"], ["kk", "Kazakh"], ["km", "Khmer"], ["ko", "Korean"], ["ku", "Kurdish (Kurmanji)"], ["ky", "Kyrgyz"], ["lo", "Lao"], ["la", "Latin"], ["lv", "Latvian"], ["lt", "Lithuanian"], ["lb", "Luxembourgish"], ["mk", "Macedonian"], ["mg", "Malagasy"], ["ms", "Malay"], ["ml", "Malayalam"], ["mt", "Maltese"], ["mi", "Maori"], ["mr", "Marathi"], ["mn", "Mongolian"], ["my", "Myanmar (Burmese)"], ["ne", "Nepali"], ["no", "Norwegian"], ["ps", "Pashto"], ["fa", "Persian"], ["pl", "Polish"], ["pt", "Portuguese"], ["pa", "Punjabi"], ["ro", "Romanian"], ["ru", "Russian"], ["sm", "Samoan"], ["gd", "Scots Gaelic"], ["sr", "Serbian"], ["st", "Sesotho"], ["sn", "Shona"], ["sd", "Sindhi"], ["si", "Sinhala"], ["sk", "Slovak"], ["sl", "Slovenian"], ["so", "Somali"], ["es", "Spanish"], ["su", "Sundanese"], ["sw", "Swahili"], ["sv", "Swedish"], ["tg", "Tajik"], ["ta", "Tamil"], ["te", "Telugu"], ["th", "Thai"], ["tr", "Turkish"], ["uk", "Ukrainian"], ["ur", "Urdu"], ["uz", "Uzbek"], ["vi", "Vietnamese"], ["cy", "Welsh"], ["xh", "Xhosa"], ["yi", "Yiddish"], ["yo", "Yoruba"], ["zu", "Zulu"]];
      j+='<div style="float:left;"><ul class="translate-dd" style="list-style:none;">';
      j+='			<li><a href="#" id="viewDisclaimer" class="translate-dd-link">View Disclaimer</a></li>';
      j+='			<li><a href="'+h+'es" class="translateLink translate-dd-link">Spanish</a></li>';
      j+='			<li><a href="'+h+'ht" class="translateLink translate-dd-link">Haitian Creole</a></li>';
      j+='			<li><a href="'+h+'pt" class="translateLink translate-dd-link">Portuguese</a></li>';
      j+='			<li><a href="'+h+'fr" class="translateLink translate-dd-link">French</a></li>';
      j+='			<li><a href="'+h+'zh-CN" class="translateLink translate-dd-link">Chinese - Simplified</a></li>';
      j+='			<li><a href="'+h+'vi" class="translateLink translate-dd-link">Vietnamese</a></li>';
      j+='			<li><a href="'+h+'ru" class="translateLink translate-dd-link">Russian</a></li>';
      j+='			<li><a href="'+h+'so" class="translateLink translate-dd-link">Somali</a></li>';
      j+='			<li><a href="'+h+'ar" class="translateLink translate-dd-link">Arabic</a></li>';
      j+="<hr>";
      for(var i=0;
        i<30;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+='<div  style="float:left;"><ul class="translate-dd" style="list-style:none;">';
      for(var i=30;
        i<66;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+='<div  style="float:left;"><ul class="translate-dd" style="list-style:none;">';
      for(var i=66;
        i<g.length;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+="</div>";
      j+='<div id="translateMessage" class="translate-message"><a href="#" id="closeDisclaimer" class="md-cb"></a>'+this.writeDisclaimer()+"</div>";
      let e=jQuery("#overlay");
      e.html(j)
    }
    , toggleLanguages:function() {
      let f=document.getElementById("overlay");
      f.style.visibility=(f.style.visibility=="visible")?"hidden": "visible";
      let g=document.getElementById("overlay-background");
      g.style.visibility=(g.style.visibility=="visible")?"hidden": "visible";
      let e=document.getElementById("languages");
      e.style.display=(e.style.display=="block")?"none": "block"
    }
    , showDisclaimer:function() {
      let f=document.getElementById("translateMessage");
      f.style.display=(f.style.display=="block")?"none": "block";
      let e=document.getElementById("languages");
      e.style.display=(e.style.display=="block")?"none": "block"
    }
    , selectDisclaimerLanguage:function() {
      let k=document.getElementById("translateForm");
      let j=document.getElementById("disclaimerLanguageSelect");
      let g=document.getElementById("translateMessage");
      if(g!=null) {
        let h=g.getElementsByClassName("disclaimer");
        for(let e=0;
          e<h.length;
          e++) {
          let f=h[e];
          if(f.id==j.options[j.selectedIndex].value) {
            f.style.display="inherit"
          }
          else {
            f.style.display="none"
          }
        }
      }
    }
    , selectTranslateLanguage:function(e) {
      let g=document.getElementById("translateForm");
      let f=document.getElementById("translateLanguageSelect");
      if(g!=null&&e!=null&&e.value!="") {
        g.tl.value=e.value;
        g.submit()
      }
    }
  }
}
();

