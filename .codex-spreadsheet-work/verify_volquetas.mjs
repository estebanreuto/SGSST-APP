import { FileBlob, SpreadsheetFile } from "@oai/artifact-tool";

const path = "/Applications/XAMPP/xamppfiles/htdocs/SGSST-APP/outputs/volquetas_metas_20260613/control_mensual_volquetas.xlsx";
const input = await FileBlob.load(path);
const wb = await SpreadsheetFile.importXlsx(input);
const registro = wb.worksheets.getItem("Registro diario");
const gastos = wb.worksheets.getItem("Gastos volqueta");
const aportes = wb.worksheets.getItem("Aportes tios");

registro.getRange("D7:H7").values = [[10, 5, 2, 3, 4]];
registro.getRange("D8:H8").values = [[20, 0, 0, 0, 0]];
gastos.getRange("A7:A9").values = [
  [new Date("2026-06-01T00:00:00")],
  [new Date("2026-06-02T00:00:00")],
  [new Date("2026-05-02T00:00:00")],
];
gastos.getRange("C7:G9").values = [
  ["ACPM", "Tanqueo", 200000, "Caja", "Nequi"],
  ["Mantenimiento", "Arreglo", 100000, "Juancho", "Davivienda"],
  ["ACPM", "Otro mes", 999999, "Caja", "Efectivo"],
];
aportes.getRange("A7:A9").values = [
  [new Date("2026-06-03T00:00:00")],
  [new Date("2026-06-03T00:00:00")],
  [new Date("2026-05-03T00:00:00")],
];
aportes.getRange("C7:F9").values = [
  ["Juancho", "Envio gastos", 100000, "Nequi"],
  ["Beto", "Envio gastos", 50000, "Bancolombia"],
  ["Beto", "Otro mes", 999999, "Daviplata"],
];

const test = await wb.inspect({
  kind: "table",
  range: "'Registro diario'!A6:O8",
  include: "values,formulas",
  tableMaxRows: 4,
  tableMaxCols: 15,
  maxChars: 8000,
});
console.log(test.ndjson);

const finance = await wb.inspect({
  kind: "table",
  range: "Resumen!A32:J41",
  include: "values,formulas",
  tableMaxRows: 12,
  tableMaxCols: 10,
  maxChars: 10000,
});
console.log(finance.ndjson);

const errors = await wb.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 100 },
});
console.log(errors.ndjson);
