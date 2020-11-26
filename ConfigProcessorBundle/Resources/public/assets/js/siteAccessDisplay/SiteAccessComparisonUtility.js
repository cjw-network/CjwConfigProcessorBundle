class SiteAccessComparisonUtility {
  singleSiteAccessViewButton;
  normalComparisonSelectButton;
  commonParamSelectButton;
  uncommonParamSelectButton;

  constructor() {
    this.singleSiteAccessViewButton = document.querySelector(
      "[cjw_id=cjw_single_sa_view]"
    );
    this.normalComparisonSelectButton = document.querySelector(
      "[cjw_id=cjw_show_normal_comparison]"
    );
    this.commonParamSelectButton = document.querySelector(
      "[cjw_id=cjw_show_common_parameters]"
    );
    this.uncommonParamSelectButton = document.querySelector(
      "[cjw_id=cjw_show_uncommon_parameters]"
    );
  }

  setUpTheUtilityButtons() {
    if (this.singleSiteAccessViewButton) {
      this.singleSiteAccessViewButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.redirectToLimitedView();
      };
    }

    if (this.normalComparisonSelectButton) {
      this.normalComparisonSelectButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.redirectToLimitedView("unlimited");
      };
    }

    if (this.commonParamSelectButton) {
      this.commonParamSelectButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.redirectToLimitedView("commons");
      };
    }

    if (this.uncommonParamSelectButton) {
      this.uncommonParamSelectButton.onclick = (event) => {
        event.preventDefault();
        event.stopPropagation();

        this.redirectToLimitedView("uncommons");
      };
    }
  }

  redirectToLimitedView(limiterKeyWord = null) {
    if (
      limiterKeyWord &&
      (limiterKeyWord === "commons" || limiterKeyWord === "uncommons")
    ) {
      let firstSiteAccess = document.querySelector(".first_list");
      let secondSiteAccess = document.querySelector(".second_list");

      firstSiteAccess = firstSiteAccess
        ? firstSiteAccess.getAttribute("siteaccess")
        : "";
      secondSiteAccess = secondSiteAccess
        ? secondSiteAccess.getAttribute("siteaccess")
        : "";

      window.location = `/admin/cjw/config-processing/parameter_list/compare/${firstSiteAccess}/${secondSiteAccess}/${limiterKeyWord}`;
    } else if (limiterKeyWord === "unlimited") {
      let firstSiteAccess = document.querySelector(".first_list");
      let secondSiteAccess = document.querySelector(".second_list");

      firstSiteAccess = firstSiteAccess
        ? firstSiteAccess.getAttribute("siteaccess")
        : "";
      secondSiteAccess = secondSiteAccess
        ? secondSiteAccess.getAttribute("siteaccess")
        : "";

      window.location = `/admin/cjw/config-processing/parameter_list/compare/${firstSiteAccess}/${secondSiteAccess}`;
    } else {
      let firstSiteAccess = document.querySelector(".first_list");

      firstSiteAccess = firstSiteAccess
        ? firstSiteAccess.getAttribute("siteaccess")
        : "current";

      window.location = `/admin/cjw/config-processing/parameter_list/siteaccess/${firstSiteAccess}`;
    }
  }
}
