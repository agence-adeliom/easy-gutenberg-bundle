const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const path = require('path');

module.exports = {
    entry: './assets/ts/easy-gutenberg.ts',
    output: {
        filename: 'js/easy-gutenberg.js',
        path: path.resolve(__dirname, '../src/Resources/public'),
        library: {
            name: 'EasyGutenberg',
            type: 'umd'
        }
    },
    module: {
        rules: [
            {
                test: /\.(ts|tsx)$/i,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
            {
                test: /\.(s[ac]ss|css)$/i,
                use: [
                    MiniCssExtractPlugin.loader,
                    'css-loader',
                    'sass-loader'
                ]
            }
        ]
    },
    resolve: {
        extensions: ['.ts', '.tsx', '.js'],
    },
    externals: {
        'react': 'React',
        'react-dom': 'ReactDOM'
    },
    optimization: {
        minimizer: [ `...`, new CssMinimizerPlugin() ]
    },
    plugins: [
        new MiniCssExtractPlugin({
            filename: 'css/easy-gutenberg.css',
        }),
    ]
};
