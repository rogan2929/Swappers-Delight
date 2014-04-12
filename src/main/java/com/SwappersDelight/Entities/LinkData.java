/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

package com.SwappersDelight.Entities;

/**
 * Base Link Data
 * 
 * @author rogan2929
 */
public class LinkData {
    private String caption;
    private String href;
    private String name;
    private String description;
    private ImageObject src;

    public ImageObject getSrc() {
        return src;
    }

    public void setSrc(ImageObject src) {
        this.src = src;
    }

    public String getCaption() {
        return caption;
    }

    public void setCaption(String caption) {
        this.caption = caption;
    }

    public String getHref() {
        return href;
    }

    public void setHref(String href) {
        this.href = href;
    }

    public String getName() {
        return name;
    }

    public void setName(String name) {
        this.name = name;
    }

    public String getDescription() {
        return description;
    }

    public void setDescription(String description) {
        this.description = description;
    }
    
    
}
