function main() {
  let rootElement = UI.getElement("root");
  let mainView = new MainView();

  rootElement.appendChild(mainView.render());
}

main();
