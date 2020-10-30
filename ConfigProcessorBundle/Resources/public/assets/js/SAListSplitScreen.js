class SAListSplitScreen {
  splitViewButton;

  constructor() {
    this.splitViewButton = document.querySelector("#cjw_split_selection");
  }

  setUpSplitViewButton() {
    if (this.splitViewButton) {
      this.splitViewButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.initiateSplitView();
      };
    }
  }

  initiateSplitView() {
    if (this.splitViewButton) {
      this.splitViewButton.classList.add("switch");

      const sendButton = document.querySelector(
        "#sendSplitViewSiteAccessesButton"
      );

      if (sendButton) {
        sendButton.classList.remove("transparentButton");
        sendButton.onclick = (event) => {
          event.preventDefault();

          this.splitViewIniationRequest();
        };
      }

      const siteAccessSwitchList = document.querySelectorAll(
        ".cjw_site_access_list > li > a"
      );

      if (siteAccessSwitchList) {
        for (const siteAccess of siteAccessSwitchList) {
          siteAccess.classList.add("deactivated_link");

          siteAccess.onclick = (event) => {
            event.preventDefault();
            event.stopPropagation();

            if (!siteAccess.classList.contains("switch")) {
              const selectedSiteAccesses = document.querySelectorAll(
                ".cjw_site_access_list > li > .switch"
              );

              if (selectedSiteAccesses && selectedSiteAccesses.length === 2) {
                selectedSiteAccesses[0].classList.toggle("switch");
              }
            }

            siteAccess.classList.toggle("switch");
          };
        }
      }

      this.splitViewButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.removeSplitView();
      };
    }
  }

  removeSplitView() {
    if (this.splitViewButton) {
      this.splitViewButton.classList.remove("switch");

      const sendButton = document.querySelector(
        "#sendSplitViewSiteAccessesButton"
      );

      if (sendButton) {
        sendButton.classList.add("transparentButton");
      }

      const siteAccessSwitchList = document.querySelectorAll(
        ".cjw_site_access_list > li > a"
      );

      if (siteAccessSwitchList) {
        for (const siteAccess of siteAccessSwitchList) {
          siteAccess.classList.remove("deactivated_link");
          siteAccess.classList.remove("switch");

          siteAccess.onclick = (event) => {
            event.default();
          };
        }
      }

      this.splitViewButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.initiateSplitView();
      };
    }
  }

  splitViewIniationRequest() {
    const selectedSiteAccesses = document.querySelectorAll(
      ".cjw_site_access_list > li > .switch"
    );

    if (selectedSiteAccesses && selectedSiteAccesses.length === 2) {
      const firstSA = selectedSiteAccesses[0].innerText;
      const secondSA = selectedSiteAccesses[1].innerText;

      window.location = `/admin/cjw/config-processing/parameter_list/compare/${firstSA}/${secondSA}`;
    } else {
      alert("There have to be exactly 2 site accesses selected!");
    }
  }
}
