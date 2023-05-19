/* This is just a tool I use to convert card sets into arrays.
It is not actually used anywhere in the program and can safley be deleted. */

const fs = require("fs");

let input = fs.readFileSync("./index.txt").toString();

let array = input.split('\n');

fs.writeFileSync("./output.json", JSON.stringify(array));