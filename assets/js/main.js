function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    const tab = document.getElementById(tabName);
    if(tab) {
        tab.style.display = "block";
    }

    if(evt && evt.currentTarget) {
      evt.currentTarget.className += " active";
    } else {
      const activeTab = document.querySelector(`.tab-link[onclick*="'${tabName}'"]`);
      if(activeTab) activeTab.classList.add('active');
    }
}

function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

window.addEventListener('DOMContentLoaded', (event) => {
    var tabName = getUrlParameter('tab');
    if (tabName) {
        openTab(null, tabName);
    }
});
