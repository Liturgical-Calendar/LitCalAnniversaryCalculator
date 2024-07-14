const fs = require('fs').promises;

const NA2LANGS = {
    "en": "N/A",
    "fr": "N/D",
    "es": "N/D",
    "de": "N/V",
    "pt": "N/D",
    "la": "non pertinet"
}

const langs = ['en', 'fr','es','de','pt','la'];
const langsFiles = {
    en: 'data/i18n/en.json',
    fr: 'data/i18n/fr.json',
    es: 'data/i18n/es.json',
    de: 'data/i18n/de.json',
    pt: 'data/i18n/pt.json',
    la: 'data/i18n/la.json'
};

let srcLanguageObj = null;

async function* readFiles() {
    for (const lang of langs) {
        const file = langsFiles[lang];
        const stats = await fs.stat(file);
        if (stats.isFile()) {
            let fileContent = await fs.readFile(file, "utf8");
            const fileObj = JSON.parse(fileContent);
            yield {lang: lang, content: fileObj};
        }
    }
}

const runFix = async () => {
    let runningCount = 0;
    let runningFixCount = 0;
    const srcLanguageObjStr = await fs.readFile(`data/i18n/it.json`, 'utf8');
    srcLanguageObj = JSON.parse(srcLanguageObjStr);
    if (srcLanguageObj) {
        console.log('retrieved srcLanguageObj');
        const langFilesContents = readFiles();
        let isDone = false;
        while(isDone === false) {
            const langFileResult = await langFilesContents.next();
            const {value, done} = langFileResult;
            isDone = done;
            console.log(`done: ${done}`);
            if(done) {
                console.log(`totalCount: ${runningCount}, totalFixCount: ${runningFixCount}`);
            }
            else {
                const {entryCount, fixCount} = await checkObjectKeys(value);
                console.log(`runFix: ${entryCount} entries contained N/A values, ${fixCount} entries were adjusted for lang ${value.lang}`);
                runningCount += entryCount;
                runningFixCount += fixCount;
            }
        }
    }
}

const checkObjectKeys = async (valueObj) => {
    let entryCount = 0;
    let fixCount = 0;
    const {content, lang} = valueObj;
    Object.entries(srcLanguageObj).forEach(([key,item]) => {
        Object.entries(item).forEach(([prop, value]) => {
            if(value === "N/A") {
                entryCount++;
                //console.log(`found an N/A entry at key ${key}.${prop}`);
                if(content[key][prop] !== NA2LANGS[lang]) {
                    fixCount++;
                    content[key][prop] = NA2LANGS[lang];
                }
            }
        });
    });
    console.log(`checkObjectKeys: ${entryCount} entries contained N/A values, ${fixCount} entries were adjusted for lang ${lang}`);
    if(fixCount > 0) {
        const dataToWrite = JSON.stringify(content, null, 4);
        let writeResult = await fs.writeFile(langsFiles[lang], dataToWrite, {
            encoding: "utf8",
            flag: "w",
            mode: 0o666
        },
        (err) => {
            console.log(err);
        });
        if(writeResult === undefined) {
            console.log(`Successfully updated file ${langsFiles[lang]} with new contents`);
        }
    }
    return {
        entryCount: entryCount,
        fixCount: fixCount
    };
}

runFix();
