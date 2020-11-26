class SAListSplitScreen {
  firstSiteAccessSelector;
  secondSiteAccessSelector;

  constructor() {
    this.firstSiteAccessSelector = document.querySelector(
      "#first_site_access_selection"
    );
    this.secondSiteAccessSelector = document.querySelector(
      "#second_site_access_selection"
    );
  }

  enableRightSideBarButtons() {
    const rightSideBar = document.querySelectorAll(
      ".ez-context-menu > div > button"
    );

    if (rightSideBar) {
      for (const button of rightSideBar) {
        button.disabled = false;
      }
    }
  }

  disableRightSideBarButtons() {
    const rightSideBar = document.querySelectorAll(
      ".ez-context-menu > div > button"
    );

    if (rightSideBar) {
      for (const button of rightSideBar) {
        button.disabled = true;
      }
    }
  }

  setUpSiteAccessSelectionForSingleView() {
    if (this.firstSiteAccessSelector && this.secondSiteAccessSelector) {
      this.firstSiteAccessSelector.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.initiateSiteAccessChangeRequest(
          this.firstSiteAccessSelector.value
        );
      };

      this.secondSiteAccessSelector.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.splitViewIniationRequest();
      };
    }
  }

  setUpSiteAccessSelectionForCompareView() {
    if (this.firstSiteAccessSelector && this.secondSiteAccessSelector) {
      this.firstSiteAccessSelector.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.splitViewIniationRequest();
      };

      this.secondSiteAccessSelector.onchange = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.splitViewIniationRequest();
      };
    }
  }

  initiateSiteAccessChangeRequest(siteAccessToChangeTo) {
    if (siteAccessToChangeTo && siteAccessToChangeTo.trim().length > 0) {
      window.location = `/admin/cjw/config-processing/parameter_list/siteaccess/${siteAccessToChangeTo}`;
    }
  }

  splitViewIniationRequest() {
    if (this.firstSiteAccessSelector && this.secondSiteAccessSelector) {
      const firstSA = this.firstSiteAccessSelector.value;
      const secondSA = this.secondSiteAccessSelector.value;

      if (secondSA && secondSA.trim().length > 0) {
        window.location = `/admin/cjw/config-processing/parameter_list/compare/${firstSA}/${secondSA}`;
      }
    } else {
      alert("There have to be exactly 2 site accesses selected!");
    }
  }
}
