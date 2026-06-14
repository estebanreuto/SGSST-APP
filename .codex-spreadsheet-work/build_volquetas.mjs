import fs from "node:fs/promises";
import { SpreadsheetFile, Workbook } from "@oai/artifact-tool";

const outputDir = "/Applications/XAMPP/xamppfiles/htdocs/SGSST-APP/outputs/volquetas_metas_20260613";
const previewDir = `${outputDir}/previews`;
const outputPath = `${outputDir}/control_mensual_volquetas.xlsx`;

const wb = Workbook.create();
const dashboard = wb.worksheets.add("Resumen");
const registro = wb.worksheets.add("Registro diario");
const config = wb.worksheets.add("Configuracion");

const colors = {
  navy: "#17365D",
  blue: "#2F75B5",
  sky: "#D9EAF7",
  paleBlue: "#EAF3F8",
  green: "#548235",
  paleGreen: "#E2F0D9",
  orange: "#F4B183",
  paleOrange: "#FCE4D6",
  yellow: "#FFF2CC",
  red: "#C00000",
  paleRed: "#F4CCCC",
  gray: "#F2F2F2",
  midGray: "#D9E1F2",
  border: "#B7C9D6",
  text: "#1F2937",
  inputBlue: "#0000FF",
  linkGreen: "#008000",
  white: "#FFFFFF",
};

const currencyFormat = '$#,##0;[Red]($#,##0);-';
const percentFormat = '0.0%;[Red](0.0%);-';
const integerFormat = '#,##0;[Red](#,##0);-';

function titleBand(sheet, range, text) {
  sheet.getRange(range).merge();
  const cell = sheet.getRange(range);
  cell.values = [[text]];
  cell.format.fill = colors.navy;
  cell.format.font = { bold: true, color: colors.white, size: 18 };
  cell.format.horizontalAlignment = "center";
  cell.format.verticalAlignment = "center";
  cell.format.rowHeight = 34;
}

function sectionBand(sheet, range, text) {
  sheet.getRange(range).merge();
  const cell = sheet.getRange(range);
  cell.values = [[text]];
  cell.format.fill = colors.blue;
  cell.format.font = { bold: true, color: colors.white, size: 11 };
  cell.format.horizontalAlignment = "left";
  cell.format.verticalAlignment = "center";
  cell.format.rowHeight = 24;
}

function card(sheet, labelRange, valueRange, label, formula, fill, numberFormat) {
  sheet.getRange(labelRange).merge();
  sheet.getRange(valueRange).merge();
  const labelCell = sheet.getRange(labelRange);
  const valueCell = sheet.getRange(valueRange);
  labelCell.values = [[label]];
  labelCell.format.fill = fill;
  labelCell.format.font = { bold: true, color: colors.navy, size: 10 };
  labelCell.format.horizontalAlignment = "center";
  valueCell.formulas = [[formula]];
  valueCell.format.fill = "#FFFFFF";
  valueCell.format.font = { bold: true, color: colors.linkGreen, size: 16 };
  valueCell.format.horizontalAlignment = "center";
  valueCell.format.verticalAlignment = "center";
  valueCell.format.numberFormat = numberFormat;
  sheet.getRange(`${labelRange.split(":")[0]}:${valueRange.split(":")[1]}`).format.borders = {
    preset: "outside",
    style: "medium",
    color: colors.border,
  };
}

// Configuration
config.showGridLines = false;
titleBand(config, "A1:F2", "CONFIGURACION - CONTROL DE VOLQUETAS");
config.getRange("A3:F3").merge();
config.getRange("A3:F3").values = [[
  "Cambia solamente las celdas amarillas con letra azul. El resto del archivo se actualiza automaticamente.",
]];
config.getRange("A3:F3").format.fill = colors.paleBlue;
config.getRange("A3:F3").format.font = { italic: true, color: colors.text, size: 10 };
config.getRange("A3:F3").format.wrapText = true;
config.getRange("A3:F3").format.rowHeight = 30;

sectionBand(config, "A5:C5", "PARAMETROS DEL MES");
config.getRange("A6:B10").values = [
  ["Parametro", "Valor"],
  ["Año", 2026],
  ["Mes (1 a 12)", 6],
  ["Meta diaria lunes a jueves", 20],
  ["Porcentaje para mi", 0.22],
];
config.getRange("A6:B6").format.fill = colors.sky;
config.getRange("A6:B6").format.font = { bold: true, color: colors.navy };
config.getRange("A6:B10").format.borders = { preset: "all", style: "thin", color: colors.border };
config.getRange("B7:B10").format.fill = colors.yellow;
config.getRange("B7:B10").format.font = { color: colors.inputBlue, bold: true };
config.getRange("B7:B9").format.numberFormat = integerFormat;
config.getRange("B10").format.numberFormat = percentFormat;
config.getRange("B7").dataValidation = {
  rule: { type: "whole", operator: "between", formula1: 2020, formula2: 2100 },
};
config.getRange("B8").dataValidation = {
  rule: { type: "whole", operator: "between", formula1: 1, formula2: 12 },
};
config.getRange("B9").dataValidation = {
  rule: { type: "whole", operator: "between", formula1: 1, formula2: 200 },
};
config.getRange("B10").dataValidation = {
  rule: { type: "decimal", operator: "between", formula1: 0, formula2: 1 },
};

sectionBand(config, "A12:D12", "TARIFAS POR VIAJE");
config.getRange("A13:C18").values = [
  ["Ruta", "Tarifa", "Nota"],
  ["Rio a trituradora", 32000, "Valor segun relacion de viajes"],
  ["Rio a stock", 30000, "Valor por cada viaje"],
  ["Stock a trituradora", 16000, "Stock/STOP a trituradora"],
  ["Trituradora a stock", 12000, "Trituradora a stock/STOP"],
  ["Stock a stock", 12000, "Stock/STOP a stock/STOP"],
];
config.getRange("A13:C13").format.fill = colors.sky;
config.getRange("A13:C13").format.font = { bold: true, color: colors.navy };
config.getRange("A13:C18").format.borders = { preset: "all", style: "thin", color: colors.border };
config.getRange("B14:B18").format.fill = colors.yellow;
config.getRange("B14:B18").format.font = { color: colors.inputBlue, bold: true };
config.getRange("B14:B18").format.numberFormat = currencyFormat;
config.getRange("B14:B18").dataValidation = {
  rule: { type: "whole", operator: "between", formula1: 0, formula2: 10000000 },
};

sectionBand(config, "A20:F20", "COMO USAR EL ARCHIVO");
config.getRange("A21:F25").merge(true);
config.getRange("A21:F25").values = [
  ["1. Selecciona el año y el mes en esta hoja."],
  ["2. En Registro diario escribe la cantidad de viajes de cada ruta."],
  ["3. La meta normal es de lunes a jueves; el atraso se reparte entre viernes, sabado y domingo."],
  ["4. El valor bruto y tu 22% se calculan automaticamente."],
  ["5. Revisa el avance semanal y mensual en la hoja Resumen."],
];
config.getRange("A21:F25").format.fill = "#FFFFFF";
config.getRange("A21:F25").format.font = { color: colors.text, size: 10 };
config.getRange("A21:F25").format.wrapText = true;
config.getRange("A21:F25").format.rowHeight = 24;
config.getRange("A21:F25").format.borders = { preset: "outside", style: "thin", color: colors.border };

config.getRange("A:A").format.columnWidth = 30;
config.getRange("B:B").format.columnWidth = 18;
config.getRange("C:C").format.columnWidth = 28;
config.getRange("D:F").format.columnWidth = 14;
config.freezePanes.freezeRows(5);

// Daily register
registro.showGridLines = false;
titleBand(registro, "A1:P2", "REGISTRO DIARIO DE VIAJES");
registro.getRange("A3:P3").merge();
registro.getRange("A3:P3").formulas = [[
  '="Mes: "&CHOOSE(Configuracion!$B$8,"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre")&" "&Configuracion!$B$7&" | Escribe los viajes en las columnas azules."',
]];
registro.getRange("A3:P3").format.fill = colors.paleBlue;
registro.getRange("A3:P3").format.font = { bold: true, color: colors.linkGreen, size: 10 };
registro.getRange("A3:P3").format.horizontalAlignment = "center";
registro.getRange("A3:P3").format.rowHeight = 26;

registro.getRange("A5:P5").merge();
registro.getRange("A5:P5").values = [[
  "Meta: 20 viajes de lunes a jueves. Los viajes pendientes se distribuyen automaticamente entre viernes, sabado y domingo.",
]];
registro.getRange("A5:P5").format.fill = colors.yellow;
registro.getRange("A5:P5").format.font = { bold: true, color: colors.navy, size: 10 };
registro.getRange("A5:P5").format.horizontalAlignment = "center";

const headers = [
  "Fecha",
  "Dia",
  "Semana",
  "Rio a trituradora",
  "Rio a stock",
  "Stock a trituradora",
  "Trituradora a stock",
  "Stock a stock",
  "Total viajes",
  "Meta base L-J",
  "Meta sugerida hoy",
  "Faltan semana",
  "Valor bruto",
  "Mi 22%",
  "Estado",
  "Observaciones",
];
registro.getRange("A6:P6").values = [headers];
registro.getRange("A6:P6").format.fill = colors.navy;
registro.getRange("A6:P6").format.font = { bold: true, color: colors.white, size: 9 };
registro.getRange("A6:P6").format.horizontalAlignment = "center";
registro.getRange("A6:P6").format.verticalAlignment = "center";
registro.getRange("A6:P6").format.wrapText = true;
registro.getRange("A6:P6").format.rowHeight = 36;

for (let row = 7; row <= 37; row += 1) {
  const dayNumber = row - 6;
  registro.getRange(`A${row}`).formulas = [[
    `=IF(${dayNumber}<=DAY(EOMONTH(DATE(Configuracion!$B$7,Configuracion!$B$8,1),0)),DATE(Configuracion!$B$7,Configuracion!$B$8,${dayNumber}),"")`,
  ]];
  registro.getRange(`B${row}`).formulas = [[
    `=IF(A${row}="","",CHOOSE(WEEKDAY(A${row},2),"Lunes","Martes","Miercoles","Jueves","Viernes","Sabado","Domingo"))`,
  ]];
  registro.getRange(`C${row}`).formulas = [[
    `=IF(A${row}="","",INT((DAY(A${row})+WEEKDAY(DATE(YEAR(A${row}),MONTH(A${row}),1),2)-2)/7)+1)`,
  ]];
  registro.getRange(`I${row}`).formulas = [[
    `=IF(A${row}="","",SUM(D${row}:H${row}))`,
  ]];
  registro.getRange(`J${row}`).formulas = [[
    `=IF(A${row}="","",IF(WEEKDAY(A${row},2)<=4,Configuracion!$B$9,0))`,
  ]];
  registro.getRange(`K${row}`).formulas = [[
    `=IF(A${row}="","",IF(WEEKDAY(A${row},2)<=4,Configuracion!$B$9,IF(SUMIFS($J$7:$J$37,$C$7:$C$37,C${row})=0,0,ROUNDUP(MAX(0,SUMIFS($J$7:$J$37,$C$7:$C$37,C${row})-SUMIFS($I$7:$I$37,$C$7:$C$37,C${row},$A$7:$A$37,"<"&A${row}))/(8-WEEKDAY(A${row},2)),0))))`,
  ]];
  registro.getRange(`L${row}`).formulas = [[
    `=IF(A${row}="","",MAX(0,SUMIFS($J$7:$J$37,$C$7:$C$37,C${row})-SUMIFS($I$7:$I$37,$C$7:$C$37,C${row},$A$7:$A$37,"<="&A${row})))`,
  ]];
  registro.getRange(`M${row}`).formulas = [[
    `=IF(A${row}="","",D${row}*Configuracion!$B$14+E${row}*Configuracion!$B$15+F${row}*Configuracion!$B$16+G${row}*Configuracion!$B$17+H${row}*Configuracion!$B$18)`,
  ]];
  registro.getRange(`N${row}`).formulas = [[
    `=IF(A${row}="","",M${row}*Configuracion!$B$10)`,
  ]];
  registro.getRange(`O${row}`).formulas = [[
    `=IF(A${row}="","",IF(I${row}=0,"",IF(L${row}=0,"Meta cumplida",IF(WEEKDAY(A${row},2)<=4,"En curso","Recuperando"))))`,
  ]];
}

registro.getRange("A7:P37").format.borders = { preset: "all", style: "thin", color: "#DCE6EC" };
registro.getRange("A7:P37").format.font = { color: colors.text, size: 9 };
registro.getRange("A7:P37").format.rowHeight = 21;
registro.getRange("D7:H37").format.fill = colors.paleBlue;
registro.getRange("D7:H37").format.font = { color: colors.inputBlue, bold: true };
registro.getRange("P7:P37").format.fill = colors.yellow;
registro.getRange("P7:P37").format.font = { color: colors.inputBlue };
registro.getRange("A7:C37").format.font = { color: colors.linkGreen };
registro.getRange("I7:O37").format.font = { color: "#000000" };
registro.getRange("A7:A37").format.numberFormat = "dd-mmm-yyyy";
registro.getRange("C7:L37").format.numberFormat = integerFormat;
registro.getRange("M7:N37").format.numberFormat = currencyFormat;
registro.getRange("D7:H37").dataValidation = {
  rule: { type: "whole", operator: "between", formula1: 0, formula2: 500 },
};
registro.getRange("I7:I37").conditionalFormats.add("dataBar", {
  color: colors.blue,
  gradient: true,
});
registro.getRange("L7:L37").conditionalFormats.add("cellIs", {
  operator: "greaterThan",
  formula: 0,
  format: { fill: colors.paleRed, font: { color: colors.red, bold: true } },
});
registro.getRange("O7:O37").conditionalFormats.add("containsText", {
  text: "Meta cumplida",
  format: { fill: colors.paleGreen, font: { color: colors.green, bold: true } },
});
registro.getRange("O7:O37").conditionalFormats.add("containsText", {
  text: "Recuperando",
  format: { fill: colors.paleOrange, font: { color: "#9C5700", bold: true } },
});

registro.tables.add("A6:P37", true, "TablaViajes");
registro.freezePanes.freezeRows(6);
registro.freezePanes.freezeColumns(3);
const widths = [13, 13, 9, 18, 15, 20, 20, 14, 12, 13, 16, 14, 15, 14, 17, 26];
for (let i = 0; i < widths.length; i += 1) {
  registro.getRangeByIndexes(0, i, 37, 1).format.columnWidth = widths[i];
}

// Dashboard
dashboard.showGridLines = false;
titleBand(dashboard, "A1:J2", "RESUMEN MENSUAL - VOLQUETAS");
dashboard.getRange("A3:J3").merge();
dashboard.getRange("A3:J3").formulas = [[
  '="Periodo: "&CHOOSE(Configuracion!$B$8,"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre")&" "&Configuracion!$B$7',
]];
dashboard.getRange("A3:J3").format.fill = colors.paleBlue;
dashboard.getRange("A3:J3").format.font = { bold: true, color: colors.linkGreen, size: 11 };
dashboard.getRange("A3:J3").format.horizontalAlignment = "center";
dashboard.getRange("A3:J3").format.rowHeight = 24;

card(dashboard, "A5:B5", "A6:B7", "META MENSUAL", '=SUM(\'Registro diario\'!$J$7:$J$37)', colors.sky, integerFormat);
card(dashboard, "D5:E5", "D6:E7", "VIAJES REALIZADOS", '=SUM(\'Registro diario\'!$I$7:$I$37)', colors.paleGreen, integerFormat);
card(dashboard, "G5:H5", "G6:H7", "VIAJES PENDIENTES", '=MAX(0,A6-D6)', colors.paleOrange, integerFormat);
card(dashboard, "I5:J5", "I6:J7", "AVANCE DE META", '=IF(A6=0,0,D6/A6)', colors.yellow, percentFormat);

card(dashboard, "A9:B9", "A10:B11", "VALOR BRUTO", '=SUM(\'Registro diario\'!$M$7:$M$37)', colors.sky, currencyFormat);
card(dashboard, "D9:E9", "D10:E11", "MI GANANCIA (22%)", '=SUM(\'Registro diario\'!$N$7:$N$37)', colors.paleGreen, currencyFormat);
card(dashboard, "G9:H9", "G10:H11", "DIAS TRABAJADOS", '=COUNTIF(\'Registro diario\'!$I$7:$I$37,">0")', colors.paleOrange, integerFormat);
card(dashboard, "I9:J9", "I10:J11", "PROMEDIO POR DIA", '=IF(G10=0,0,D6/G10)', colors.yellow, '0.0');

sectionBand(dashboard, "A13:G13", "RESUMEN POR SEMANA");
dashboard.getRange("A14:G20").values = [
  ["Semana", "Meta", "Realizados", "Pendientes", "% Cumplido", "Valor bruto", "Mi 22%"],
  ["Semana 1", null, null, null, null, null, null],
  ["Semana 2", null, null, null, null, null, null],
  ["Semana 3", null, null, null, null, null, null],
  ["Semana 4", null, null, null, null, null, null],
  ["Semana 5", null, null, null, null, null, null],
  ["Semana 6", null, null, null, null, null, null],
];
dashboard.getRange("A14:G14").format.fill = colors.navy;
dashboard.getRange("A14:G14").format.font = { bold: true, color: colors.white, size: 9 };
dashboard.getRange("A14:G20").format.borders = { preset: "all", style: "thin", color: colors.border };
dashboard.getRange("A15:A20").format.fill = colors.gray;
dashboard.getRange("A15:A20").format.font = { bold: true, color: colors.navy };

for (let row = 15; row <= 20; row += 1) {
  const week = row - 14;
  dashboard.getRange(`B${row}`).formulas = [[
    `=SUMIF('Registro diario'!$C$7:$C$37,${week},'Registro diario'!$J$7:$J$37)`,
  ]];
  dashboard.getRange(`C${row}`).formulas = [[
    `=SUMIF('Registro diario'!$C$7:$C$37,${week},'Registro diario'!$I$7:$I$37)`,
  ]];
  dashboard.getRange(`D${row}`).formulas = [[`=MAX(0,B${row}-C${row})`]];
  dashboard.getRange(`E${row}`).formulas = [[`=IF(B${row}=0,"",C${row}/B${row})`]];
  dashboard.getRange(`F${row}`).formulas = [[
    `=SUMIF('Registro diario'!$C$7:$C$37,${week},'Registro diario'!$M$7:$M$37)`,
  ]];
  dashboard.getRange(`G${row}`).formulas = [[
    `=SUMIF('Registro diario'!$C$7:$C$37,${week},'Registro diario'!$N$7:$N$37)`,
  ]];
}
dashboard.getRange("B15:C20").format.numberFormat = integerFormat;
dashboard.getRange("D15:D20").format.numberFormat = integerFormat;
dashboard.getRange("E15:E20").format.numberFormat = percentFormat;
dashboard.getRange("F15:G20").format.numberFormat = currencyFormat;
dashboard.getRange("B15:G20").format.font = { color: colors.linkGreen };
dashboard.getRange("D15:D20").conditionalFormats.add("cellIs", {
  operator: "greaterThan",
  formula: 0,
  format: { fill: colors.paleRed, font: { color: colors.red, bold: true } },
});
dashboard.getRange("E15:E20").conditionalFormats.add("dataBar", {
  color: colors.green,
  gradient: true,
});

sectionBand(dashboard, "A23:G23", "DETALLE POR RUTA");
dashboard.getRange("A24:D30").values = [
  ["Ruta", "Viajes", "Tarifa", "Valor generado"],
  ["Rio a trituradora", null, null, null],
  ["Rio a stock", null, null, null],
  ["Stock a trituradora", null, null, null],
  ["Trituradora a stock", null, null, null],
  ["Stock a stock", null, null, null],
  ["TOTAL", null, null, null],
];
dashboard.getRange("A24:D24").format.fill = colors.navy;
dashboard.getRange("A24:D24").format.font = { bold: true, color: colors.white };
dashboard.getRange("A24:D30").format.borders = { preset: "all", style: "thin", color: colors.border };
dashboard.getRange("B25").formulas = [["=SUM('Registro diario'!$D$7:$D$37)"]];
dashboard.getRange("B26").formulas = [["=SUM('Registro diario'!$E$7:$E$37)"]];
dashboard.getRange("B27").formulas = [["=SUM('Registro diario'!$F$7:$F$37)"]];
dashboard.getRange("B28").formulas = [["=SUM('Registro diario'!$G$7:$G$37)"]];
dashboard.getRange("B29").formulas = [["=SUM('Registro diario'!$H$7:$H$37)"]];
dashboard.getRange("B30").formulas = [["=SUM(B25:B29)"]];
dashboard.getRange("C25").formulas = [["=Configuracion!$B$14"]];
dashboard.getRange("C26").formulas = [["=Configuracion!$B$15"]];
dashboard.getRange("C27").formulas = [["=Configuracion!$B$16"]];
dashboard.getRange("C28").formulas = [["=Configuracion!$B$17"]];
dashboard.getRange("C29").formulas = [["=Configuracion!$B$18"]];
dashboard.getRange("D25").formulas = [["=B25*C25"]];
dashboard.getRange("D26").formulas = [["=B26*C26"]];
dashboard.getRange("D27").formulas = [["=B27*C27"]];
dashboard.getRange("D28").formulas = [["=B28*C28"]];
dashboard.getRange("D29").formulas = [["=B29*C29"]];
dashboard.getRange("D30").formulas = [["=SUM(D25:D29)"]];
dashboard.getRange("A30:D30").format.fill = colors.sky;
dashboard.getRange("A30:D30").format.font = { bold: true, color: colors.navy };
dashboard.getRange("B25:B30").format.numberFormat = integerFormat;
dashboard.getRange("C25:D30").format.numberFormat = currencyFormat;
dashboard.getRange("B25:D30").format.font = { color: colors.linkGreen };

dashboard.getRange("I14:K20").values = [
  ["Semana", "Meta", "Realizados"],
  ["S1", null, null],
  ["S2", null, null],
  ["S3", null, null],
  ["S4", null, null],
  ["S5", null, null],
  ["S6", null, null],
];
for (let row = 15; row <= 20; row += 1) {
  dashboard.getRange(`J${row}`).formulas = [[`=B${row}`]];
  dashboard.getRange(`K${row}`).formulas = [[`=C${row}`]];
}
dashboard.getRange("I14:K20").format.font = { color: "#FFFFFF", size: 1 };
dashboard.getRange("I14:K20").format.fill = "#FFFFFF";

const chart = dashboard.charts.add("line", dashboard.getRange("I14:K20"));
chart.setPosition("I13", "P28");
chart.title = "Meta vs viajes realizados por semana";
chart.titleTextStyle.fontSize = 12;
chart.hasLegend = true;
chart.xAxis = { axisType: "textAxis" };
chart.yAxis = { numberFormatCode: "0" };
if (chart.series.items.length >= 2) {
  chart.series.items[0].fill = colors.orange;
  chart.series.items[0].line = { color: colors.orange, width: 2 };
  chart.series.items[1].fill = colors.blue;
  chart.series.items[1].line = { color: colors.blue, width: 2 };
}

dashboard.getRange("A32:J33").merge();
dashboard.getRange("A32:J33").values = [[
  "Lectura rapida: si Viajes pendientes llega a 0, cumpliste la meta mensual. En Registro diario, Meta sugerida hoy indica cuantos viajes deberias hacer ese dia para mantenerte o recuperar el ritmo semanal.",
]];
dashboard.getRange("A32:J33").format.fill = colors.paleBlue;
dashboard.getRange("A32:J33").format.font = { italic: true, color: colors.text, size: 9 };
dashboard.getRange("A32:J33").format.wrapText = true;
dashboard.getRange("A32:J33").format.borders = { preset: "outside", style: "thin", color: colors.border };
dashboard.getRange("A32:J33").format.rowHeight = 32;

const dashboardWidths = [19, 14, 13, 19, 14, 14, 19, 14, 12, 14, 14, 14, 14, 14, 14, 14];
for (let i = 0; i < dashboardWidths.length; i += 1) {
  dashboard.getRangeByIndexes(0, i, 33, 1).format.columnWidth = dashboardWidths[i];
}
dashboard.freezePanes.freezeRows(3);

await fs.mkdir(previewDir, { recursive: true });
const inspectSummary = await wb.inspect({
  kind: "table",
  range: "Resumen!A1:G30",
  include: "values,formulas",
  tableMaxRows: 30,
  tableMaxCols: 8,
  maxChars: 12000,
});
console.log("SUMMARY_INSPECT");
console.log(inspectSummary.ndjson);

const inspectRegister = await wb.inspect({
  kind: "table",
  range: "'Registro diario'!A1:P12",
  include: "values,formulas",
  tableMaxRows: 14,
  tableMaxCols: 14,
  maxChars: 12000,
});
console.log("REGISTER_INSPECT");
console.log(inspectRegister.ndjson);

const errors = await wb.inspect({
  kind: "match",
  searchTerm: "#REF!|#DIV/0!|#VALUE!|#NAME\\?|#N/A",
  options: { useRegex: true, maxResults: 300 },
  summary: "final formula error scan",
});
console.log("ERROR_SCAN");
console.log(errors.ndjson);

for (const [sheetName, range, fileName] of [
  ["Resumen", "A1:P33", "resumen.png"],
  ["Registro diario", "A1:P37", "registro.png"],
  ["Configuracion", "A1:F25", "configuracion.png"],
]) {
  const preview = await wb.render({ sheetName, range, scale: 1.35, format: "png" });
  await fs.writeFile(`${previewDir}/${fileName}`, new Uint8Array(await preview.arrayBuffer()));
}

await fs.mkdir(outputDir, { recursive: true });
const exported = await SpreadsheetFile.exportXlsx(wb);
await exported.save(outputPath);
console.log(`OUTPUT=${outputPath}`);
