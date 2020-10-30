document.addEventListener("DOMContentLoaded", () => {
  let parameterDisplay = new ParameterDisplay(
    new ParameterBranchDisplay(
      document.querySelectorAll(".param_list > .param_list_items")
    )
  );

  let parameterLocationRetriever = new ParameterLocationRetrieval();

  parameterDisplay.cleanUpList();
  parameterLocationRetriever.setUpLocationRetrievalButtons();
});
