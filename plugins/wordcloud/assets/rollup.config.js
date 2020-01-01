// rollup.config.js
import babel from 'rollup-plugin-babel';
import resolve from 'rollup-plugin-node-resolve';
import commonjs from 'rollup-plugin-commonjs';

let typeToBuild = 'd3';
// let typeToBuild = 'wc2';

export default {
  input: 'src/main'+typeToBuild+'.src.js',
  output: {
    file: 'build/wordcloud'+typeToBuild+'.js',
    format: 'umd',
    name: 'WordCloudFactory'
  },
  plugins: [
    commonjs({
      namedExports: {
        'node_modules/file-saver/dist/FileSaver.min.js': [ 'saveAs' ]
      }
    }),
    resolve(),
    babel({
      exclude: 'node_modules/**' // only transpile our source code
    })
  ]
};