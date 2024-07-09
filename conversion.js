/* This is just a tool I use to convert card sets into arrays.
It is not actually used anywhere in the program and can safley be deleted. */

const fs = require("fs");
let files = fs.readdirSync("./cards/");
files.pop();
files.pop();

files.forEach(file=> {
    let input = fs.readFileSync("./cards/"+file).toString();

    let array = input.split('\r\n');

    fs.writeFileSync("./output/"+file.slice(0,-4)+".json", JSON.stringify(array));
});