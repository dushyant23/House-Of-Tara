
================================================================
GraphQL
================================================================


================================================================
GET CCAVANUE PAYMENT URL
================================================================

mutation {
    ccavanuePayment(
        input:{
            order_increment_id: "000000152"
            callBackUrl: "ccavenue/payment/success"
        }
    ){
        success
        gatewayurl
        encRequest
        access_code
    }
}


{
    "data": {
        "ccavanuePayment": {
            "success": "1",
            "gatewayurl": "https://test.ccavenue.com/transaction/transaction.do?command=initiateTransaction",
            "encRequest": "f1ceb7a1827242fc20ab21194bf700dd3ed00d1c7588c27a46e55e6d632732eebd2bdc162d3470d6b17ba127f7e6ca5398e7e5a78b1272b3395650401726508064e7d582b49ae8450f2dc79ceb262dd88c2688456eb8dafab47e3b58113b19090fb1d0a20e74baf881c8e4f3cd1eb36496eaf5e8f8cb23cae4c55d61adb2d8a2c25e7c424f3a898c6e275831cde410565c480748b5df6bd2adba346fc0540e2584886371ac4cd6a2717f4fda55b86beb2b7136495aa3fbbb06fe3a5faa2f614aea5efed7889eaf983a3f559c43ab9d8c34eb919a9697b7e34acd5237e2b81ed1352696da28723ddfe3a0d424a887e1682d47e9680b48b7b49dc4e4ad34ececd449b5e866f62dfad9a64a410e4bc27fcf1f2d98fd2c4ce280e5b27bceed9ca98d15ce5ea10b7116c7c8cbc1a00809fa816dbb13e870c5795045e0625bae15c937a795c59bfa53fedec9b082c224a9496ecd0c3addc7db42bacfb0941c62c62fc8eea08cf0a3e598f7a88b2917ece96b2ec15dd9b9b4e6edb9f2eae71e21c87f82d23e0e7a07ed0a94f7de8902d44f26a4b11dfef0fcb71b54b05add5fcbb6f1efb68d4f9169e7b29adee0e61b9386765214f307ffe894b0db1d0b24f889945617e81bcff004ee6d7318f605228dd0746344512c9c26aa0ac163e2eae61c21335068d8fb70051f966599e28a2d9717ba2d38e55053ae7c83bcc01b3b8a0002af9a20de975bb067e5967cabde1f6e5cd508775a37a9d02dce6ec5a986a1bfa1f3566061a6a7e3e6866027cc425ae98dd6e82d3090d2aa3d4c6246d4ff21d1ca21aa5abbbe89e8b6fc2ef9f037b96c62a7b49b1a9a7f89332ba52c214f6b13c1601397caeadbe0fbd953bb94dbea853823fbd4de2ba3664906f616472f15c67667f43df3ccf5312d89da66f5324fa8107bc5b4d360efec43f6195413b13fa5fc46a0",
            "access_code": "asd"
        }
    }
}


================================================================



================================================================
CCAVANUE COMPLETE
================================================================

mutation {
    ccavanueComplete(
        input:{
            orderNo:"000000152"
            encResp: "g/TSyvzSOUTDTXqyzBQmlA/tE8TH/r9XiTGL7AOXkVnwzhs+jwYuerMDD2ezHEj86GjzR61Ug4xDLcX5P5ef2w=="
        }
    ){
        success
        order_id
    }
}




================================================================
