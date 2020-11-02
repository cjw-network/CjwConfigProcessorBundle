document.addEventListener("DOMContentLoaded", () => {
  const saListSplitView = new SAListSplitScreen();

  if (document.querySelector(".param_list")) {
    saListSplitView.disableRightSideBarButtons();
    saListSplitView.setUpSiteAccessSelectionForSingleView();
  }

  if (
    document.querySelector(".first_list") &&
    document.querySelector(".second_list")
  ) {
    const siteAccessComparisonUtility = new SiteAccessComparisonUtility();
    const differenceHighlighter = new SiteAccessDifferencesHighlighter();

    saListSplitView.enableRightSideBarButtons();
    saListSplitView.setUpSiteAccessSelectionForCompareView();

    siteAccessComparisonUtility.setUpTheUtilityButtons();
    differenceHighlighter.setUpFunctionality();
  }
});
