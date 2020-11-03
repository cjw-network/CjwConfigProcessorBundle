document.addEventListener("DOMContentLoaded", function () {
  let searchBarUtility = new SearchBarUtility();
  let scrollUpButtonDisplay = new ScrollUpButtonDisplay();

  searchBarUtility.setUpSearchBar();
  scrollUpButtonDisplay.setUpScrollUpButton();

  if (
    document.querySelector(".param_list") ||
    document.querySelector(".compare_display")
  ) {
    let parameterDisplay = new ParameterDisplay();
    let parameterLocationRetriever = new ParameterLocationRetrieval();

    let paramBranchDisplay = new ParameterBranchDisplay(
      document.querySelectorAll(".open_subtree")
    );

    parameterDisplay.cleanUpList();
    parameterLocationRetriever.setUpLocationRetrievalButtons();

    paramBranchDisplay.subTreeOpenClickListener();
    paramBranchDisplay.setDoubleClickListenerForRemainingNodes();

    const loadingCircle = document.querySelector("#loading_circle");

    if (loadingCircle) {
      setTimeout(() => {
        const tableHeaderWithLoadingCircle = document.querySelector(
          ".param_list"
        );

        if (document.querySelector("#loading_circle")) {
          tableHeaderWithLoadingCircle?.removeChild(loadingCircle);
        }
      }, 500);
    }
  }

  if (document.querySelector(".cjw_site_access_selectors")) {
    let saListSplitView = new SAListSplitScreen();

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
      differenceHighlighter.setUpHighlighterButton();
    }
  }
});
