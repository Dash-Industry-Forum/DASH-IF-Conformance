function main() {
  let mainView = new MainView();

  //Make mainView globally accessible
  window.mainView = mainView;

  //rootElement.appendChild(mainView.render());
  mainView.render("root");
}

main();
