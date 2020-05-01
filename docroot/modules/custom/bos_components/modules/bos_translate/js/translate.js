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
          e.toggleLanguages()
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
      g+='<form id="translateForm"><label for="disclaimerLanguageSelect">View Disclaimer in: </label><select id="disclaimerLanguageSelect" name="disclaimerLanguageSelect" class="form-control mb-3">';
      g+='<option value="English">English</option>';
      g+='<option value="Spanish">Spanish</option>';
      g+='<option value="ChineseS">Chinese - Simplified</option>';
      g+='<option value="ChineseT">Chinese - Traditional</option></select>';
      let h='<div id="English" class="disclaimer"><h4>Google Translate Disclaimer</h4>';
      h+="<p>The Boston Department of Information Technology (“DoIT”) offers translations of the content through Google Translate. Because Google Translate is an external website, DoIT does not control the quality or accuracy of translated content. All DoIT content is filtered through Google Translate which may result in unexpected and unpredictable degradation of portions of text, images and the general appearance on translated pages. Google Translate may maintain unique privacy and use policies. These policies are not controlled by DoIT and are not associated with DoIT’s privacy and use policies. After selecting a translation option, users will be notified that they are leaving DoIT’s website. Users should consult the original English content on DoIT’s website if there are any questions about the translated content.</p>";
      h+="<p>DoIT uses Google Translate to provide language translations of its content. Google Translate is a free, automated service that relies on data and technology to provide its translations. The Google Translate feature is provided for informational purposes only. Translations cannot be guaranteed as exact or without the inclusion of incorrect or inappropriate language. Google Translate is a third-party service and site users will be leaving DoIT to utilize translated content. As such, DoIT does not guarantee and does not accept responsibility for, the accuracy, reliability, or performance of this service nor the limitations provided by this service, such as the inability to translate specific files like PDFs and graphics (e.g. .jpgs, .gifs, etc.).</p>";
      h+="<p>DoIT provides Google Translate as an online tool for its users, but DoIT does not directly endorse the website or imply that it is the only solution available to users. All site visitors may choose to use alternate tools for their translation needs. Any individuals or parties that use DoIT content in translated form, whether by Google Translate or by any other translation services, do so at their own risk. DoIT is not liable for any loss or damages arising out of, or issues related to, the use of or reliance on translated content. DoIT assumes no liability for any site visitor’s activities in connection with use of the Google Translate functionality or content.</p>";
      h+="<p>The Google Translate service is a means by which DoIT offers translations of content and is meant solely for the convenience of non-English speaking users of the website. The translated content is provided directly and dynamically by Google; DoIT has no direct control over the translated content as it appears using this tool. Therefore, in all contexts, the English content, as directly provided by DoIT is to be held authoritative.</p></div>";
      let j='<div id="Spanish" class="disclaimer" style="display:none;"><h4>Exención de Responsabilidad del Traductor Google</h4>';
      j+="<p>El Departamento de Tecnología de la Información de Boston (\"DoIT\") ofrece traducciones del contenido a través de Google Translate. Debido a que Google Translate es un sitio web externo, DoIT no controla la calidad ni la precisión del contenido traducido. Todo el contenido de DoIT se filtra a través del Traductor de Google, lo que puede provocar una degradación inesperada e impredecible de partes de texto, imágenes y la apariencia general de las páginas traducidas. Google Translate puede mantener políticas únicas de privacidad y uso. Estas políticas no están controladas por DoIT y no están asociadas con las políticas de privacidad y uso de DoIT. Después de seleccionar una opción de traducción, se notificará a los usuarios que abandonan el sitio web de DoIT. Los usuarios deben consultar el contenido original en inglés en el sitio web de DoIT si hay alguna pregunta sobre el contenido traducido.</p>";
      j+="<p>El DoIT usa el Traductor Google para proporcionar traducciones lingüísticas de su contenido. El Traductor Google es un servicio gratis y automatizado que se basa en datos y tecnología para proporcionar sus traducciones. La función del Traductor Google es proporcionada solamente para propósitos informativos. Las traducciones no pueden ser garantizadas como exactas o sin la inclusión de lenguaje incorrecto o inapropiado. El Traductor Google es un servicio de terceros y los usuarios del sitio dejarán al DoIT para utilizar el contenido traducido. Como tal, el DoIT no garantiza y no acepta responsabilidad por la exactitud, confiabilidad o desempeño de este servicio o de las limitaciones proporcionadas por este servicio, tales como la inhabilidad de traducir archivos específicos como PDF y gráficos (p.e. .jpgs, .gifs, etc.).</p>";
      j+="<p>El DoIT proporciona el Traductor Google como una herramienta en línea para sus usuarios, pero el DoIT no endosa directamente el sitio web o implica que es la única solución disponible para los usuarios. Todos los visitantes al sitio pueden escoger usar herramientas alternativas para sus necesidades de traducción. Cualquier persona que utilice el contenido del DoIT en su forma traducida, ya sea por el Traductor Google o por cualquier otro servicio de traducción, lo hace bajo su propio riesgo. El DoIT no es responsable por ninguna pérdida o daño que surja de, o problemas relacionados con el uso o dependencia del contenido traducido. El DoIT no asume ninguna responsabilidad por las actividades de los visitantes del sitio en conexión con el uso de la funcionalidad o contenido del Traductor Google.</p>";
      j+="<p>El servicio del Traductor Google es un medio por el cual el DoIT ofrece traducciones de contenido y está destinado solamente para la conveniencia de los usuarios del sitio web que no hablan inglés. El contenido traducido es proporcionado directa y dinámicamente por Google; el DoIT no tiene control directo sobre el contenido traducido tal y como aparece utilizando esta herramienta. Por lo tanto, en todos los contextos, el contenido en inglés, tal y como se proporciona por el DoIT será considerado como el autorizado.</p></div>";
      let e='<div id="ChineseS" class="disclaimer" style="display:none;"><h4>Google翻译免责声明</h4>';
      e+="<p>波士顿信息技术部（“DoIT”）通过Google翻译提供内容的翻译。由于Google翻译是外部网站，因此DoIT不能控制翻译内容的质量或准确性。所有DoIT内容均通过Google翻译过滤，这可能会导致文本，图像部分以及翻译页面的整体外观出现意外和不可预测的下降。 Google翻译可能会维护独特的隐私权和使用政策。这些政策不受DoIT的控制，并且与DoIT的隐私和使用政策无关。选择翻译选项后，将通知用户他们即将离开DoIT网站。如果对翻译后的内容有任何疑问，用户应查阅DoIT网站上的原始英语内容。</p>";
      e+="<p>DoIT使用Google翻译为其网站内容提供语言翻译服务。Google翻译是一项免费的自动服务，其依靠相关数据和技术来提供翻译服务。提供Google翻译服务的目的仅为提供相关信息，因此DoIT无法保证翻译后的内容与原文完全相同或不包含任何不正确或不适宜的语言。Google翻译是一项第三方服务，而DoIT网站使用者将离开DoIT网站以便查阅翻译后的内容。因此，DoIT并不保证这项服务的准确性、可靠性、质量和局限性（如这项服务无法翻译PDF、图形（如jpgs、gifs等）格式的文件），也不对此负责。</p>";
      e+="<p>Google翻译是DoIT为其网站使用者提供的一种网上工具。尽管如此，DoIT并不直接为该网站服务提供担保，也不表明使用者只能使用Google翻译提供的服务。所有DoIT网站访问者可以选择使用其它工具以满足其翻译需要。任何使用翻译后（无论是通过Google翻译还是通过其它翻译服务）的DoIT网站内容的个人或机构应自行承担风险。DoIT不对因使用或依赖翻译后的内容所造成的损失、损害或问题负责。DoIT不对任何网站访问者与使用Google翻译功能或内容相关的活动负责。</p>";
      e+="<p>Google翻译服务是DoIT为其网站使用者提供的一种翻译工具，其唯一的目的是为英语非母语的网站使用者提供方便。Google直接提供动态的内容翻译服务，而DoIT不直接控制翻译后的内容，即使其使用该工具。因此，在各种情况下，使用者应以DoIT为其直接提供的英文内容为准。</p></div>";
      let f='<div id="ChineseT" class="disclaimer" style="display:none;"><h4>Google翻譯免責聲明</h4>';
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
      j+='<div id="languages"><a class="md-cb" href="#" id="langCloseButton"></a>';
      let g=[["af", "Afrikaans"], ["sq", "Albanian"], ["am", "Amharic"], ["ar", "Arabic"], ["hy", "Armenian"], ["az", "Azerbaijani"], ["eu", "Basque"], ["be", "Belarusian"], ["bn", "Bengali"], ["bs", "Bosnian"], ["bg", "Bulgarian"], ["ca", "Catalan"], ["ceb", "Cebuano"], ["ny", "Chichewa"], ["co", "Corsican"], ["hr", "Croatian"], ["cs", "Czech"], ["da", "Danish"], ["nl", "Dutch"], ["eo", "Esperanto"], ["et", "Estonian"], ["tl", "Filipino"], ["fi", "Finnish"], ["fr", "French"], ["fy", "Frisian"], ["gl", "Galician"], ["ka", "Georgian"], ["de", "German"], ["el", "Greek"], ["gu", "Gujarati"], ["ht", "Haitian Creole"], ["ha", "Hausa"], ["haw", "Hawaiian"], ["iw", "Hebrew"], ["hi", "Hindi"], ["hmn", "Hmong"], ["hu", "Hungarian"], ["is", "Icelandic"], ["ig", "Igbo"], ["id", "Indonesian"], ["ga", "Irish"], ["it", "Italian"], ["ja", "Japanese"], ["jw", "Javanese"], ["kn", "Kannada"], ["kk", "Kazakh"], ["km", "Khmer"], ["ko", "Korean"], ["ku", "Kurdish (Kurmanji)"], ["ky", "Kyrgyz"], ["lo", "Lao"], ["la", "Latin"], ["lv", "Latvian"], ["lt", "Lithuanian"], ["lb", "Luxembourgish"], ["mk", "Macedonian"], ["mg", "Malagasy"], ["ms", "Malay"], ["ml", "Malayalam"], ["mt", "Maltese"], ["mi", "Maori"], ["mr", "Marathi"], ["mn", "Mongolian"], ["my", "Myanmar (Burmese)"], ["ne", "Nepali"], ["no", "Norwegian"], ["ps", "Pashto"], ["fa", "Persian"], ["pl", "Polish"], ["pt", "Portuguese"], ["pa", "Punjabi"], ["ro", "Romanian"], ["ru", "Russian"], ["sm", "Samoan"], ["gd", "Scots Gaelic"], ["sr", "Serbian"], ["st", "Sesotho"], ["sn", "Shona"], ["sd", "Sindhi"], ["si", "Sinhala"], ["sk", "Slovak"], ["sl", "Slovenian"], ["so", "Somali"], ["su", "Sundanese"], ["sw", "Swahili"], ["sv", "Swedish"], ["tg", "Tajik"], ["ta", "Tamil"], ["te", "Telugu"], ["th", "Thai"], ["tr", "Turkish"], ["uk", "Ukrainian"], ["ur", "Urdu"], ["uz", "Uzbek"], ["vi", "Vietnamese"], ["cy", "Welsh"], ["xh", "Xhosa"], ["yi", "Yiddish"], ["yo", "Yoruba"], ["zu", "Zulu"]];
      j+='<div style="float:left;"><ul style="list-style:none;">';
      j+='			<li><a href="#" id="viewDisclaimer" class="translate-dd-link">View Disclaimer</a></li>';
      j+='			<li><a href="'+h+'es" class="translateLink translate-dd-link">Spanish</a></li>';
      j+='			<li><a href="'+h+'zh-CN" class="translateLink translate-dd-link">Chinese - Simplified</a></li>';
      j+='			<li><a href="'+h+'zh-TW" class="translateLink translate-dd-link">Chinese - Traditional</a></li>';
      j+="<hr>";
      for(var i=0;
        i<30;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+='<div  style="float:left;"><ul style="list-style:none;">';
      for(var i=30;
        i<66;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+='<div  style="float:left;"><ul style="list-style:none;">';
      for(var i=66;
        i<g.length;
        i++) {
        j+='<li><a href="'+h+g[i][0]+'" class="translateLink translate-dd-link">'+g[i][1]+"</a></li>"
      }
      j+=" 		</ul></div>";
      j+="</div>";
      j+='<div id="translateMessage"><a href="#" id="closeDisclaimer" class="md-cb"></a>'+this.writeDisclaimer()+"</div>";
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

