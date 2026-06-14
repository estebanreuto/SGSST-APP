import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const path = "/Applications/XAMPP/xamppfiles/htdocs/SGSST-APP/outputs/volquetas_metas_20260613/control_mensual_volquetas.xlsx";
const input = await FileBlob.load(path);
const wb = await SpreadsheetFile.importXlsx(input);
const registro = wb.worksheets.getItem("Registro diario");

registro.getRange("D7:H7").values = [[10, 5, 2, 3, 4]];
registro.getRange("D8:H8").values = [[20, 0, 0, 0, 0]];

const test = await wb.inspect({
  kind: "table",
  range: "'Registro diario'!A6:O8",
  include: "values,formulas",
  tableMaxRows: 4,
  tableMaxCols: 15,
  maxChars: 8000,
});
console.log(test.ndjson);

const errors = await wb.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
});
console.log(errors.ndjson);
