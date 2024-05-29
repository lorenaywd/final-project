const express = require('express')
const mongoose = require('mongoose')
const Product = require('./Models/productModel')
const app = express()
const cors = require('cors');
app.use(cors())
app.use(express.json())

//routes
app.get('/', (req,res)=>{
    res.send('Hello Node API')
})

app.get('/test', (req,res)=>{
    res.send('Hello test je m\'appel Lorena')
})

app.get('/products', async(req,res)=>{
    try{
        const products = await Product.find({})
        res.status(200).json(products);

    }catch(error){
        console.log(error.message)
        res.status(500).json({message: error.message})
    }
})

app.get('/products/:id', async(req,res)=>{
    try{
        const products = await Product.find({})
        res.status(200).json(products);

    }catch(error){
        console.log(error.message)
        res.status(500).json({message: error.message})
    }
})


app.post('/product',async(req,res)=>{
    console.log(req.body);
    try{
        const product = await Product.create(req.body)
        res.status(201).json(product);

    }catch(error){
        console.log(error.message)
        res.status(500).json({message: error.message})
    }
})

mongoose.
connect('mongodb://localhost:27017/CleanThisLogs')
.then(()=>{
    console.log('connected to mongoDB')
    app.listen(3000, ()=>{
        console.log('Node API app is running on port 3000')
    });
}).catch((error)=>{
    console.log(error)
})