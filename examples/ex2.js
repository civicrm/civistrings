alert("Ignore first");
alert(ts("First" ));
alert(ts("Second", {1: "whiz"}));
alert(ts( 'Third'));
alert(clients("Ignore second"));
alert(clients("Ignore third", {1: "whiz"}));
function whits() {
  for (a in b) {
    mitts("wallaby", function(zoo) {
      alert(zoo + ts("Fourth"));
    });
  }
}
alert(ts( "Fifth") + "-" + ts("Sixth"));
alert(ts(message));
alert(ts("Embedded ts(\'example\')"));
ts("Special\ncharacters");
ts('Special\ncharacters');
ts("more \$special characters");
ts('even more \\$special characters', {});
ts("Mixed 'quote' \"marks\"");
ts('Mixed "quote" \'marks\'');
{
  ts('Singular', {count: 2, plural: "Plurals %count"});
}
ts("Singular %1", {
  "plural": "Plurals %1 %count",
  "1": "1",
});
