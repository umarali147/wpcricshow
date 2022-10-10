let uris = {
  links: {
    star1:
      "https://gocast123.me/embed.php?player=desktop&v=star1in&vw=100%&vh=520",
    willow: "https://stream.crichd.vip/update/willowcricket.php",
    sportslive: "https://cricplay2.xyz/ptv-sports",
    willowhd: "https://stream.crichd.vip/update/willowextra.php",
    starhindi:
      "https://gocast123.me/embed.php?player=desktop&v=starhindi&vw=100%&vh=520",
  },
  links_with_ads: {
    astro: "https://cricplay2.xyz/astro-cricket",
    acc: "https://livetvon.click/cricfree/stream-370.php",
  },
  othercns: {
    tensports: "http://cricfree.live/live/embed/ten-sports",

    supersports: "https://cricplay2.xyz/supersports-cricket",
    tt: "https://livetvon.click/cricfree/stream-370.php",
    ptv: "https://cdn2.crichd.pro/embed2.php?id=ptvsp",
    testing: "https://cdn2.crichd.pro/embed2.php?id=ptvsp",
  },
};

function gotoHome() {
  window.location.href = "https://cricshow.live";
}
let links = uris.links;
Object.keys(links).forEach((link) => {
  let buttonHtml;
  if (link === "willow") {
    buttonHtml = `<button class="mdc-button mdc-button--outlined mr-5 w-15 active" name="${link}">
    <span class="mdc-button__ripple"></span>
    <span class="mdc-button__label active" >${link}</span>
  </button>`;
  } else {
    buttonHtml = `<button class="mdc-button mdc-button--outlined mr-5 w-15" name="${link}">
    <span class="mdc-button__ripple"></span>
    <span class="mdc-button__label" >${link}</span>
  </button>`;
  }
  $("#button-container").append(buttonHtml);
});

function fnBrowserDetect() {
  let userAgent = navigator.userAgent;
  let browserName;

  if (userAgent.match(/chrome|chromium|crios/i)) {
    browserName = "chrome";
  } else if (userAgent.match(/firefox|fxios/i)) {
    browserName = "firefox";
  } else if (userAgent.match(/safari/i)) {
    browserName = "safari";
  } else if (userAgent.match(/opr\//i)) {
    browserName = "opera";
  } else if (userAgent.match(/edg/i)) {
    browserName = "edge";
  } else {
    browserName = "No browser detection";
  }
  return browserName;
}
function updateSandBox(cn) {
  let iframz = document.getElementById("iframz");
  iframz.src = links[cn];
  if (cn === "willow" || cn === "willowhd") {
    if (fnBrowserDetect() === "chrome") {
      iframz.removeAttribute("sandbox");
    } else {
      if (!iframz.hasAttribute("sandbox")) {
        iframz.sandbox =
          "allow-forms allow-pointer-lock allow-same-origin allow-scripts allow-top-navigation";
      }
    }
  } else {
    if (!iframz.hasAttribute("sandbox")) {
      iframz.sandbox =
        "allow-forms allow-pointer-lock allow-same-origin allow-scripts allow-top-navigation";
    }
  }
}
$(document).delegate(".mdc-button", "click", function () {
  console.log("testin");
  $(".mdc-button").removeClass("active");
  $(".mdc-button").find(".mdc-button__label").removeClass("active");
  $(this).addClass("active");
  $(this).find(".mdc-button__label").addClass("active");
  updateSandBox($(this).find(".mdc-button__label").text());
});

updateSandBox("willow");
