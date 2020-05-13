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
      g+='<option value="Haitian">Haitian Creole</option>';
      g+='<option value="Portuguese">Portuguese</option>';
      g+='<option value="French">French</option>';
      g+='<option value="ChineseS">Chinese - Simplified</option>';
      g+='<option value="Vietnamese">Vietnamese</option>';
      g+='<option value="Russian">Russian</option>';
      g+='<option value="Somali">Somali</option>';
      g+='<option value="Arabic">Arabic</option></select>';
      let h='<div id="English" class="translate-disclaimer disclaimer"><h4>About Translations on Boston.gov</h4>';
      h+="<p>The City of Boston Department of Innovation and Technology (“DoIT”) offers translations of the content on Boston.gov through the Google Translate web translator (translate.google.com). Because Google Translate is an external website, DoIT does not control the quality or accuracy of translated content. This may result in inaccurate translated text, or other errors in images and the general appearance of translated pages.</p>";
      h+="<p>DoIT uses Google Translate to provide language translations of its content. Google Translate is a free, automated service that relies on data and technology to provide its translations. The Google Translate feature is provided for informational purposes only. Translations cannot be guaranteed as exact or without the inclusion of incorrect or inappropriate language. Google Translate is a third-party service and site users will be leaving DoIT to utilize translated content. As such, DoIT does not guarantee and does not accept responsibility for, the accuracy, reliability, or performance of this service nor the limitations provided by this service, such as the inability to translate specific files like PDFs and graphics (e.g. .jpgs, .gifs, etc.).</p>";
      h+="<p>However, you can report incorrect or substandard translations and contribute better translations using Google Translate.</p>";
      h+="<ol><li>First, hover over and click on any text containing an error. A pop up box should appear.</li>";
      h+="<li>Next, click “Contribute a better translation”.</li>";
      h+="<li>Double click the area of the pop up that reads “Click a word for alternative translations, or double-click to edit directly.”</li>";
      h+="<li>Make your edits directly to the text in the text box.</li>";
      h+="<li>Finally, press Contribute to contribute your suggested edits.</li></ol>";
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
      j+="<p>El Departamento de Innovación y Tecnología de la Ciudad de Boston (”DoIT”) ofrece traducciones del contenido en Boston.gov a través del traductor web Google Translate (translate.google.com). Porque Google Translate es un sitio web externo , DoIT no controla la calidad ni la precisión del contenido traducido. Esto puede resultar en texto traducido incorrecto u otros errores en las imágenes y la apariencia general de las páginas traducidas.</p>";
      j+="<p>DoIT utiliza Google Translate para proporcionar traducciones de su contenido al idioma. Google Translate es un servicio gratuito y automatizado que se basa en datos y tecnología para proporcionar sus traducciones. La función Google Translate se proporciona únicamente con fines informativos. Las traducciones no pueden estar garantizado como exacto o sin la inclusión de un lenguaje incorrecto o inapropiado. Google Translate es un servicio de terceros y los usuarios del sitio dejarán DoIT para utilizar el contenido traducido. Como tal, DoIT no garantiza y no acepta responsabilidad por la precisión , la fiabilidad o el rendimiento de este servicio ni las limitaciones proporcionadas por este servicio, como la imposibilidad de traducir archivos específicos como PDF y gráficos (por ejemplo, .jpgs, .gifs, etc.).</p>";
      j+="<p>Sin embargo, puede informar traducciones incorrectas o de calidad inferior y contribuir con mejores traducciones usando Google Translate.</p>";
      j+="<ol><li>Primero, desplace el mouse y haga clic en cualquier texto que contenga un error. Debería aparecer un cuadro emergente.</li>";
      j+="<li>A continuación, haga clic en ”Contribuir a una mejor traducción”.</li>";
      j+="<li>Haga doble clic en el área de la ventana emergente que dic ”Haga clic en una palabra para traducciones alternativas, o haga doble clic para editar directamente.”</li>";
      j+="<li>Realice sus ediciones directamente al texto en el cuadro de texto.</li>";
      j+="<li>Finalmente, presione Contribuir para contribuir con las ediciones sugeridas.</li></ol>";
      j+="<p>Puede encontrar más información sobre cómo contribuir al Traductor de Google <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'> aquí.</a></p>";
      j+="<p>Tenga en cuenta que DoIT no controla el proceso mediante el cual las traducciones contribuidas se incorporan al traductor web de Google.</p>";
      j+="<p>La Ciudad de Boston se compromete a mejorar la calidad y la amplitud del contenido multilingüe en nuestro sitio web. La información crítica sobre la respuesta de Boston a la emergencia del coronavirus ya está disponible en varios idiomas y se puede encontrar aquí:</p>";
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
      let e='<div id="ChineseS" class="translate-disclaimer disclaimer" style="display:none;"><h4>关于Boston.gov上的翻译</h4>';
      e+="<p>波士顿市创新技术局（“DoIT”）通过Google翻译网络翻译器（translate.google.com）在Boston.gov上提供内容的翻译。 由于Google Translate是外部网站，因此DoIT不能控制翻译内容的质量或准确性。 这可能会导致翻译文本不正确，或图像中的其他错误以及翻译页面的整体外观。</p>";
      e+="<p>DoIT使用Google翻译提供其内容的语言翻译。 Google翻译是一项免费的自动化服务，依赖于数据和技术来提供其翻译。 提供Google翻译功能仅供参考。 不保证翻译的准确性或不包含错误或不适当的语言。 Google翻译是一项第三方服务，网站用户将离开DoIT来使用翻译后的内容。 因此，DoIT对本服务的准确性，可靠性或性能或本服务提供的限制（例如，无法翻译PDF和图形（例如.jpg 、. gif等）。</p>";
      e+="<p>但是，您可以报告不正确或不合格的翻译，并使用Google翻译来提供更好的翻译。</p>";
      e+="<ol><li>首先，将鼠标悬停并单击任何包含错误的文本。应出现一个弹出框。</li>";
      e+="<li>下一步，单击“贡献更好的翻译”。</li>";
      e+="<li>双击弹出窗口中的区域，即“单击单词以进行其他翻译，或双击以直接编辑。”</li>";
      e+="<li>直接对文本框中的文本进行编辑。</li>";
      e+="<li>最后，按贡献来贡献您的建议编辑。</li></ol>";
      e+="<p>有关对Google翻译做出贡献的更多信息，可在此处<a href='https://support.google.com/translate/answer/2534530?hl=zh_CN&ref_topic=7010955'>找到。</a></p>";
      e+="<p>请注意，DoIT不能控制将贡献的翻译内容整合到Google网络翻译器中的过程。</p>";
      e+="<p>波士顿市致力于提高我们网站上多语种内容的质量和广度。有关波士顿对冠状病毒应急反应的重要信息已经以多种语言提供，可以在以下网址找到</p>";
      e+="<p>西班牙语：<a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      e+="<p> Haitian Creole：<a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      e+="<p>佛得角（Cape Verdean）：<a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      e+="<p>葡萄牙语：<a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      e+="<p>法语：<a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      e+="<p>中文：<a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      e+="<p>越南语：<a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      e+="<p>俄语：<a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru </a></p>";
      e+="<p>索马里语：<a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      e+="<p>阿拉伯语：<a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'> boss.gov/co ar</a></p></div>";
      let f='<div id="Haitian" class="translate-disclaimer disclaimer" style="display:none;"><h4>Sou tradiksyon sou Boston.gov</h4>';
      f+="<p>Depatman Inovasyon ak Teknoloji Vil Boston (”DoIT”) ofri tradiksyon kontni sou Boston.gov nan tradiktè entènèt Google Translate (translate.google.com). Paske Google Translate se yon sit entènèt ekstèn , DoIT pa kontwole bon jan kalite a oswa presizyon nan kontni tradui. Sa a pouvwa rezilta nan kòrèk tèks tradui, oswa lòt erè nan imaj ak aparans la an jeneral nan paj tradui.</p>";
      f+="<p>DoIT itilize Google Translate pou founi tradiksyon lang yo nan kontni li yo. Google Translate se yon sèvis gratis, otomatik ki depann sou done ak teknoloji pou bay tradiksyon li yo .. se karakteristik nan Google Translate bay pou rezon sèlman enfòmasyon. Google Translate se yon sèvis twazyèm pati ak itilizatè sit yo pral kite DOIT yo sèvi ak tradwi kontni.Se konsa, DoIT pa garanti epi yo pa aksepte responsablite pou, presizyon an. , fyab, oswa pèfòmans nan sèvis sa a, ni limit yo bay nan sèvis sa a, tankou enkapasite a tradui dosye espesifik tankou pdf ak grafik (eg .jpgs, .gifs, elatriye).</p>";
      f+="<p>Sepandan, ou ka rapòte tradiksyon ki pa kòrèk oswa medyòm epi kontribye pi byen tradiksyon lè l sèvi avèk Google Translate.</p>";
      f+="<ol><li>Premyèman, monte sou epi klike sou nenpòt ki tèks ki gen yon erè. Yon bwat pòp moute ta dwe parèt.</li>";
      f+="<li>Apre sa, klike sou ”Kontribye yon pi bon tradiksyon”.</li>";
      f+="<li>Double klike sou zòn nan nan pòp moute a ki li ”Klike sou yon mo pou tradiksyon altènatif, oswa doub-klike sou edite dirèkteman.”</li>";
      f+="<li>Fè edits ou dirèkteman nan tèks la nan bwat tèks la.</li>";
      f+="<li>Finalman, peze Kontribye pou kontribiye edisyon sijere ou.</li></ol>";
      f+="<p>Plis enfòmasyon sou kontribiye nan Google Translate ka jwenn <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>isit la.</a></p>";
      f+="<p>Tanpri sonje ke DoIT pa kontwole pwosesis la pa ki kontribye tradiksyon yo enkòpore nan Google tradiktè wèb la.</p>";
      f+="<p>Vil Boston pran angajman pou amelyore kalite ak lajè kontni pale plizyè lang sou sit entènèt nou an. Enfòmasyon kritik konsènan repons Boston pou ijans coronavirus la deja disponib nan plizyè lang e ou ka jwenn li isit la:</p>";
      f+="<p>Panyòl: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      f+="<p>kreyòl ayisyen: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      f+="<p>Cape Verdean: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      f+="<p>Pòtigè: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      f+="<p>franse: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      f+="<p>Chinwa: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      f+="<p>Vyetnamyen: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      f+="<p>Ris: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      f+="<p>Somali: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      f+="<p>arab: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let m='<div id="Portuguese" class="translate-disclaimer disclaimer" style="display:none;"><h4>Sobre as traduções no Boston.gov</h4>';
      m+="<p>O Departamento de Inovação e Tecnologia da cidade de Boston (“ DoIT ”) oferece traduções do conteúdo em Boston.gov por meio do tradutor da web do Google Translate (translate.google.com). Como o Google Translate é um site externo , O DoIT não controla a qualidade ou a precisão do conteúdo traduzido. Isso pode resultar em texto traduzido impreciso ou em outros erros nas imagens e na aparência geral das páginas traduzidas.</p>";
      m+="<p>O DoIT usa o Google Translate para fornecer traduções de idiomas para o seu conteúdo. O Google Translate é um serviço gratuito e automatizado que depende de dados e tecnologia para fornecer suas traduções. O recurso Google Translate é fornecido apenas para fins informativos. As traduções não podem garantido como exato ou sem a inclusão de um idioma incorreto ou inadequado.O Google Translate é um serviço de terceiros e os usuários do site deixarão o DoIT para utilizar o conteúdo traduzido.Como tal, o DoIT não garante e não se responsabiliza pela precisão , confiabilidade ou desempenho deste serviço, nem as limitações fornecidas por ele, como a incapacidade de converter arquivos específicos, como PDFs e gráficos (por exemplo, .jpgs, .gifs etc.).</p>";
      m+="<p>No entanto, você pode denunciar traduções incorretas ou abaixo do padrão e contribuir com traduções melhores usando o Google Tradutor.</p>";
      m+="<ol>";
      m+="<li>Primeiro, passe o mouse e clique em qualquer texto que contenha um erro. Uma caixa pop-up deve aparecer.</li>";
      m+="<li>Em seguida, clique em ”Contribua para uma tradução melhor”.</li>";
      m+="<li>Clique duas vezes na área do pop-up que diz ”Clique em uma palavra para obter traduções alternativas ou clique duas vezes para editar diretamente.”</li>";
      m+="<li>Faça suas edições diretamente no texto na caixa de texto.</li>";
      m+="<li>Por fim, pressione Contribute para contribuir com as edições sugeridas.</li>";
      m+="</ol>";
      m+="<p>Mais informações sobre como contribuir para o Google Tradutor podem ser encontradas <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>aqui.</a></p>";
      m+="<p>Observe que o DoIT não controla o processo pelo qual as traduções contribuídas são incorporadas ao tradutor da web do Google.</p>";
      m+="<p>A cidade de Boston está comprometida em melhorar a qualidade e a abrangência do conteúdo multilíngue em nosso site. Informações críticas sobre a resposta de Boston à emergência do coronavírus já estão disponíveis em vários idiomas e podem ser encontradas aqui:</p>";
      m+="<p>Espanhol: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      m+="<p>Crioulo haitiano: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      m+="<p>Cabo-verdiano: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      m+="<p>Português: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      m+="<p>Francês: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      m+="<p>Chinês: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      m+="<p>Vietnamita: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      m+="<p>Russo: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      m+="<p>Somália: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      m+="<p>Árabe: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let n='<div id="French" class="translate-disclaimer disclaimer" style="display:none;"><h4>À propos des traductions sur Boston.gov</h4>';
      n+="<p>Le Département de l'innovation et de la technologie de la ville de Boston (“DoIT“) propose des traductions du contenu de Boston.gov via le traducteur Web Google Translate (translate.google.com). Parce que Google Translate est un site Web externe , DoIT ne contrôle pas la qualité ou l'exactitude du contenu traduit. Cela peut entraîner un texte traduit inexact ou d'autres erreurs dans les images et l'apparence générale des pages traduites.</p>";
      n+="<p>DoIT utilise Google Translate pour fournir des traductions linguistiques de son contenu. Google Translate est un service gratuit et automatisé qui s'appuie sur les données et la technologie pour fournir ses traductions. La fonctionnalité Google Translate est fournie à titre informatif uniquement. Les traductions ne peuvent pas être garanti comme étant exact ou sans inclure de langage incorrect ou inapproprié. Google Translate est un service tiers et les utilisateurs du site quitteront DoIT pour utiliser le contenu traduit. En tant que tel, DoIT ne garantit pas et n'accepte aucune responsabilité quant à l'exactitude , la fiabilité ou les performances de ce service, ni les limitations fournies par ce service, telles que l'impossibilité de traduire des fichiers spécifiques tels que des fichiers PDF et des graphiques (par exemple .jpgs, .gifs, etc.).</p>";
      n+="<p>Cependant, vous pouvez signaler des traductions incorrectes ou de qualité inférieure et contribuer à de meilleures traductions à l'aide de Google Translate.</p>";
      n+="<ol><li>Tout d'abord, survolez et cliquez sur tout texte contenant une erreur. Une fenêtre contextuelle devrait apparaître.</li>";
      n+="<li>Ensuite, cliquez sur “Contribuer à une meilleure traduction“.</li>";
      n+="<li>Double-cliquez sur la zone du pop-up qui se lit “Cliquez sur un mot pour des traductions alternatives, ou double-cliquez pour modifier directement.“</li>";
      n+="<li>Apportez vos modifications directement au texte dans la zone de texte.</li>";
      n+="<li>Enfin, appuyez sur Contribuer pour apporter vos modifications suggérées.</li></ol>";
      n+="<p>Vous trouverez plus d'informations sur la contribution à Google Translate <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>ici.</a></p>";
      n+="<p>Veuillez noter que DoIT ne contrôle pas le processus par lequel les traductions apportées sont incorporées dans le traducteur Web Google.</p>";
      n+="<p>La ville de Boston s'est engagée à améliorer la qualité et l'étendue du contenu multilingue sur notre site Web. Les informations essentielles concernant la réponse de Boston à l'urgence du coronavirus sont déjà disponibles en plusieurs langues et peuvent être trouvées ici:</p>";
      n+="<p>espagnol: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      n+="<p>Créole haïtien: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      n+="<p>Cap-Verdien: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      n+="<p>portugais: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      n+="<p>français: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      n+="<p>chinois: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      n+="<p>vietnamien: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      n+="<p>russe: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      n+="<p>Somalien: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      n+="<p>arabe: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let o='<div id="Vietnamese" class="translate-disclaimer disclaimer" style="display:none;"><h4>Về bản dịch trên Boston.gov</h4>';
      o+="<p>Sở Sáng tạo và Công nghệ Thành phố Boston (Tiếng DoIT xông) cung cấp các bản dịch nội dung trên Boston.gov thông qua trình dịch web của Google Dịch (translate.google.com). Bởi vì Google Dịch là một trang web bên ngoài , DoIT không kiểm soát chất lượng hoặc độ chính xác của nội dung dịch. Điều này có thể dẫn đến văn bản dịch không chính xác hoặc các lỗi khác trong hình ảnh và sự xuất hiện chung của các trang được dịch.</p>";
      o+="<p>DoIT sử dụng Google Dịch để cung cấp bản dịch ngôn ngữ cho nội dung của nó. Google Dịch là dịch vụ tự động, miễn phí dựa trên dữ liệu và công nghệ để cung cấp bản dịch. Tính năng Google Dịch chỉ được cung cấp cho mục đích thông tin. được bảo đảm chính xác hoặc không bao gồm ngôn ngữ không chính xác hoặc không phù hợp. Google Dịch là dịch vụ của bên thứ ba và người dùng trang web sẽ rời khỏi DoIT để sử dụng nội dung được dịch. Do đó, DoIT không đảm bảo và không chịu trách nhiệm về tính chính xác. , độ tin cậy hoặc hiệu suất của dịch vụ này cũng như các giới hạn do dịch vụ này cung cấp, chẳng hạn như không thể dịch các tệp cụ thể như PDF và đồ họa (ví dụ: .jpgs, .gifs, v.v.).</p>";
      o+="<p>Tuy nhiên, bạn có thể báo cáo bản dịch không chính xác hoặc không đạt tiêu chuẩn và đóng góp bản dịch tốt hơn bằng Google Dịch.</p>";
      o+="<ol><li>Đầu tiên, di chuột qua và nhấp vào bất kỳ văn bản nào có lỗi. Một hộp bật lên sẽ xuất hiện.</li>";
      o+="<li>Tiếp theo, nhấp vào Đóng góp bản dịch tốt hơn.</li>";
      o+="<li>Nhấp đúp vào khu vực bật lên để đọc Số lần nhấp vào một từ để dịch thay thế hoặc nhấp đúp để chỉnh sửa trực tiếp. LIÊN</li>";
      o+="<li>Thực hiện các chỉnh sửa của bạn trực tiếp cho văn bản trong hộp văn bản.</li>";
      o+="<li>Cuối cùng, nhấn Đóng góp để đóng góp các chỉnh sửa được đề xuất của bạn.</li></ol>";
      o+="<p>Thông tin thêm về việc đóng góp cho Google Dịch có thể được tìm thấy <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>tại đây.</a></p>";
      o+="<p>Xin lưu ý rằng DoIT không kiểm soát quá trình dịch thuật đóng góp được tích hợp vào trình dịch web của Google.</p>";
      o+="<p>hành phố Boston cam kết cải thiện chất lượng và độ rộng của nội dung đa ngôn ngữ trên trang web của chúng tôi. Thông tin quan trọng về phản ứng của Boston đối với trường hợp khẩn cấp coronavirus đã có sẵn bằng nhiều ngôn ngữ và có thể tìm thấy tại đây:</p>";
      o+="<p>Tiếng Tây Ban Nha: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      o+="<p>Haiti Creole: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      o+="<p>Cape Verdean: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      o+="<p>Tiếng Bồ Đào Nha: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      o+="<p>Tiếng Pháp: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      o+="<p>Tiếng Trung: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      o+="<p>Tiếng Việt: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      o+="<p>Tiếng Nga: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      o+="<p>Somali: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      o+="<p>Tiếng Ả Rập: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let p='<div id="Russian" class="translate-disclaimer disclaimer" style="display:none;"><h4>О переводах на Boston.gov</h4>';
      p+="<p>Департамент инноваций и технологий города Бостона (“DoIT“) предлагает переводы контента на Boston.gov через веб-переводчик Google Translate (translate.google.com). Поскольку Google Translate является внешним веб-сайтом , DoIT не контролирует качество или точность переведенного контента. Это может привести к неточному переведенному тексту или другим ошибкам в изображениях и общему виду переведенных страниц.</p>";
      p+="<p>DoIT использует Переводчик Google для предоставления языковых переводов своего контента. Переводчик Google - это бесплатная автоматизированная служба, которая использует данные и технологии для предоставления своих переводов. Функция Переводчика Google предоставляется только в информационных целях. Переводы не могут быть гарантированно точным или без указания неверного или ненадлежащего языка. Переводчик Google является сторонней службой, и пользователи сайта покидают DoIT для использования переведенного контента. Таким образом, DoIT не гарантирует и не несет ответственности за точность надежность или производительность этой службы, а также ограничения, предоставляемые этой службой, такие как невозможность перевода определенных файлов, таких как PDF-файлы и графика (например, .jpgs, .gifs и т. д.).</p>";
      p+="<p>Однако вы можете сообщать о неправильных или некачественных переводах и вносить более качественные переводы с помощью Google Translate.</p>";
      p+="<ol><li>Сначала наведите курсор мыши и щелкните любой текст, содержащий ошибку. Должно появиться всплывающее окно.</li>";
      p+="<li>Далее нажмите “Внести лучший перевод“.</li>";
      p+="<li>Дважды щелкните область всплывающего окна с надписью “Щелкните слово для альтернативных переводов или дважды щелкните, чтобы отредактировать напрямую.“</li>";
      p+="<li>Внесите изменения непосредственно в текст в текстовом поле.</li>";
      p+="<li>Наконец, нажмите Contribute для внесения предложенных вами изменений.</li></ol>";
      p+="<p>Дополнительную информацию о содействии переводчику Google можно найти <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>здесь.</a></p>";
      p+="<p>Обратите внимание, что DoIT не контролирует процесс, с помощью которого переводы, включенные в перевод, включаются в веб-переводчик Google.</p>";
      p+="<p>Город Бостон стремится улучшить качество и широту многоязычного контента на нашем веб-сайте. Критическая информация относительно реакции Бостона на чрезвычайную ситуацию с коронавирусом уже доступна на нескольких языках и находится здесь:</p>";
      p+="<p>испанский: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      p+="<p>гаитянский креольский: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      p+="<p>Кабо-Верде: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      p+="<p>португальский: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      p+="<p>Французский: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      p+="<p>Китайский: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      p+="<p>вьетнамский: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      p+="<p>русский язык: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      p+="<p>Сомали: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      p+="<p>Арабский: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let q='<div id="Somali" class="translate-disclaimer disclaimer" style="display:none;"><h4>Ku Saabsan Tarjumida bogga Boston.gov</h4>';
      q+="<p>Magaalada Boston Waaxda Cusbooneysiinta iyo Tiknolojiyadda (“DoIT“) waxay bixisaa tarjumaadda waxa kujira Boston.gov iyada oo loo marinayo turjubaanka websaydhka ee Google Translate (translate.google.com). , DoIT ma xukumaan tayada ama sax ahaanta waxyaabaha la tarjumay. Tani waxay ku dambayn kartaa qoraal aan sax ahayn oo la tarjumay, ama khaladaad kale oo ku saabsan sawirrada iyo muuqaalka guud ee bogagga la turjumay.</p>";
      q+="<p>DoIT waxay isticmaashaa Google Translate si ay u bixiso tarjumaadaha luqadeed ee ay ka kooban tahay. Google Translate waa adeeg bilaash ah, oo otomaatig ah kaas oo ku tiirsan xogta iyo tikniyoolajiyadda si loo bixiyo tarjumaadiisa. loo dammaanad qaadayo sida saxda ah ama iyada oo aan lagu soo darin luqad qaldan ama aan habooneyn Google Translate waa adeeg qeyb saddexaad ah adeegsadayaasha bogga waxay ka tagayaan DoIT si ay uga faa'iideystaan ​​waxyaabaha la tarjumay. , isku halaynta, ama waxqabadka adeegga ama xaddidnaanta ay adeeggan fidiso, sida karti darridda turjumaadda feylasha gaarka ah sida PDFs iyo sawirada (tusaale .jpgs, .gifs, iwm.).</p>";
      q+="<p>Si kastaba ha noqotee, waad soo sheegi kartaa tarjumaad qaldan ama kuwa hooseeya waxaadna gacan ka geysan kartaa tarjumaad wanaagsan adiga oo adeegsanaya Google Translate.</p>";
      q+="<ol><li>Marka hore, dul mari oo riix qoraal kasta oo qalad ku jiro. Sanduuqa kor u kaca waa inuu muuqdaa.</li>";
      q+="<li>Marka xigta, dhagsii “Ku tabaruc turjumaad wanaagsan“. </li>";
      q+="<li>laba jeer guji aagga pop-ka ee akhrinaya “Guji eray u dhiganta tarjumaadaha kale, ama laba-guji si aad toos u tafatirto.“</li>";
      q+="<li>Si toos ah ugu samee tafatirayaashaada qoraalka sanduuqa qoraalka.</li>";
      q+="<li>Ugu dambayntii, riix tabaruc si aad ugu biiriso tifatirkaaga la soo jeediyay.</li></ol>";
      q+="<p>Macluumaad dheeri ah oo ku saabsan ku biirinta Google Translate waxaa laga heli karaa <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>halkan.</a></p>";
      q+="<p>Fadlan la soco in DoIT ma xakameyso howsha tarjumaadaha tabaruca ah lagu daray tarjume webka Google.</p>";
      q+="<p>agaalada Boston waxa ka go'an inay hagaajiso tayada iyo ballaadhka waxyaabaha ku qoran luqadaha badan luqadaha kala duwan. Macluumaad muhiim ah oo ku saabsan jawaabta Boston ee xaaladda degdegga ah ee coronavirus ayaa durba lagu heli karaa luqado badan waxaana laga heli karaa halkan:</p>";
      q+="<p>Isbaanish: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      q+="<p>Haitian Creole: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      q+="<p>Cape Verdean: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      q+="<p>Boortaqiiska: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      q+="<p>Faransiis: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      q+="<p>Shiine: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      q+="<p>Fiyatnaamiis: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      q+="<p>Ruush: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      q+="<p>Af-soomaali: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      q+="<p>Carabi: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      let r='<div id="Arabic" class="translate-disclaimer disclaimer" style="display:none;direction: rtl;"><h4>Boston.gov حول الترجمات على</h4>';
      r+="<p>تقدم دائرة الابتكار والتكنولوجيا في مدينة بوسطن (“DoIT“) ترجمة للمحتوى على Boston.gov من خلال مترجم الويب الخاص بترجمة Google (translate.google.com). نظرًا لأن الترجمة من Google هي موقع ويب خارجي ، فإن DoIT لا تتحكم في جودة أو دقة المحتوى المترجم. قد يؤدي هذا إلى نص مترجم غير دقيق ، أو أخطاء أخرى في الصور والمظهر العام للصفحات المترجمة.</p>";
      r+="<p>تستخدم DoIT ترجمة Google لتقديم ترجمات لغوية لمحتواها. الترجمة من Google هي خدمة مجانية آلية تعتمد على البيانات والتكنولوجيا لتقديم ترجماتها. يتم توفير ميزة الترجمة من Google لأغراض إعلامية فقط. لا يمكن ضمان الترجمات على أنها دقيقة أو بدون تضمين لغة غير صحيحة أو غير مناسبة. الترجمة من Google هي خدمة تابعة لجهة خارجية وسيغادر مستخدمو الموقع DoIT لاستخدام المحتوى المترجم. على هذا النحو ، لا تضمن DoIT ولا تقبل المسؤولية عن دقة أو موثوقية أو أداء هذه الخدمة ولا القيود التي توفرها هذه الخدمة ، مثل عدم القدرة على ترجمة ملفات معينة مثل ملفات PDF والرسومات (مثل. jpgs ،. متحركة ، إلخ).</p>";
      r+="<p>ومع ذلك ، يمكنك الإبلاغ عن ترجمات غير صحيحة أو دون المستوى المطلوب والمساهمة في ترجمات أفضل باستخدام الترجمة من Google.</p>";
      r+="<ol><li>أولاً ، مرر الماوس فوق أي نص يحتوي على خطأ وانقر عليه. يجب أن يظهر مربع منبثق.</li>";
      r+="<li>بعد ذلك ، انقر فوق “المساهمة بترجمة أفضل“.</li>";
      r+="<li>انقر نقرًا مزدوجًا فوق منطقة النافذة المنبثقة التي تقول “انقر فوق كلمة للحصول على ترجمات بديلة ، أو انقر نقرًا مزدوجًا للتعديل مباشرة“.</li>";
      r+="<li>قم بإجراء تعديلاتك مباشرة على النص الموجود في مربع النص.</li>";
      r+="<li>أخيرًا ، اضغط على مساهمة للمساهمة بتعديلاتك المقترحة.</li></ol>";
      r+="<p>يمكن العثور على مزيد من المعلومات حول المساهمة في ترجمة Google <a href='https://support.google.com/translate/answer/2534530?hl=en&ref_topic=7010955'>هنا.</a></p>";
      r+="<p>يرجى ملاحظة أن DoIT لا تتحكم في العملية التي يتم من خلالها دمج الترجمات المساهمة في مترجم الويب من Google.</p>";
      r+="<p>تلتزم مدينة بوسطن بتحسين جودة واتساع المحتوى متعدد اللغات على موقعنا. المعلومات الهامة المتعلقة باستجابة بوسطن لحالة طوارئ الفيروسات التاجية متاحة بالفعل بلغات متعددة ويمكن العثور عليها هنا:</p>";
      r+="<p>الأسبانية: <a href='https://www.boston.gov/covid-19-es'>boston.gov/covid19-es</a></p>";
      r+="<p>الكريولية الهايتية: <a href='https://www.boston.gov/covid-19-hc'>boston.gov/covid19-hc</a></p>";
      r+="<p>الرأس الأخضر: <a href='https://www.boston.gov/covid-19-cv'>boston.gov/covid19-cv</a></p>";
      r+="<p>البرتغالية: <a href='https://www.boston.gov/covid-19-pt'>boston.gov/covid19-pt</a></p>";
      r+="<p>فرنسي: <a href='https://www.boston.gov/covid-19-fr'>boston.gov/covid19-fr</a></p>";
      r+="<p>صينى: <a href='https://www.boston.gov/covid-19-zh'>boston.gov/covid19-zh</a></p>";
      r+="<p>الفيتنامية: <a href='https://www.boston.gov/covid-19-vi'>boston.gov/covid19-vi</a></p>";
      r+="<p>الروسية: <a href='https://www.boston.gov/covid-19-ru'>boston.gov/covid19-ru</a></p>";
      r+="<p>الصومالية: <a href='https://www.boston.gov/covid-19-so'>boston.gov/covid19-so</a></p>";
      r+="<p>عربى: <a href='https://docs.google.com/document/d/1tiN7YntWJ1jdRGwBtvInccwy33mFxv-eUCvtVt9djb8/edit?ts=5e78df99#heading=h.7n9y1gfd74n7'>boston.gov/covid19-ar</a></p></div>";
      g+=h;
      g+=j;
      g+=e;
      g+=f;
      g+=m;
      g+=n;
      g+=o;
      g+=p;
      g+=q;
      g+=r;
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

