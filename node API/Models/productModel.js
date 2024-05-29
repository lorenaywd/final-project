const mongoose = require('mongoose')
const productSchema = mongoose.Schema(
    {
        loggerName:{
            type: String,
            required: true
        },
        message:{
            type: String,
            required:true
        },
        level:{
            type: String,
            required:true
        },
        data:{
            type: Array,
            required:false
        },
        user:{
            type: String,
            required:true
        }
    },
    {
        timestamps: true
    }
)

const Product = mongoose.model('Product', productSchema);
module.exports = Product;